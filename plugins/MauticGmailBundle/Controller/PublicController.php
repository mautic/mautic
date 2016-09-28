<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticGmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{

    private function doTracking(){
        $logger = $this->factory->getLogger();

        // if additional data were sent with the tracking pixel
        $query_string = $this->request->server->get('QUERY_STRING');
        if (!$query_string) {
            $logger->log('error', 'GMAIL: query string is not available');
            return;
        }

        if (strpos($query_string, 'r=') === 0)
            $query_string = substr($query_string, strpos($query_string, '?')+1); // remove route variable

        parse_str($query_string, $query);

        // URL attr 'd' is encoded so let's decode it first.
        if (!isset($query['d'], $query['sig'])) {
            $logger->log('error', 'GMAIL: query variables are not found');
            return;
        }
        // get secret from Gmail plugin settings
        $integrationHelper = $this->factory->getHelper('integration');
        $gmailIntegration = $integrationHelper->getIntegrationObject('Gmail');
        $keys = $gmailIntegration->getDecryptedApiKeys();

        // generate signature
        $salt = $keys['secret'];
        if (strpos($salt, '$1$') === false) {
            $salt = '$1$'.$salt;
        } // add MD5 prefix
        $cr = crypt(urlencode($query['d']), $salt);
        $mySig = hash('crc32b', $cr); // this hash type is used in c#

        // compare signatures
        if (hash_equals($mySig, $query['sig'])) {
            // decode and parse query variables
            $b64 = base64_decode($query['d']);
            $gz = gzdecode($b64);
            parse_str($gz, $query);
        } else {
            // signatures don't match: stop
            $logger->log('error', 'GMAIL: signatures don\'t match');

            unset($query);
        }

        if (empty($query) || !isset($query['email'], $query['subject'], $query['body'])) {
            $logger->log('error', 'GMAIL: query variables are empty');
            return;
        }

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        // email is a semicolon delimited list of emails
        $emails = explode(';', $query['email']);
        $repo = $this->getModel('lead')->getRepository();

        foreach ($emails as $email) {
            $lead = $repo->getLeadByEmail($email);
            if ($lead === null) {
                $lead = $this->createLead($email, $repo);
            }

            if ($lead === null) {
                continue;
            } // lead was not created

            $idHash = hash('crc32', $email.$query['body']);
            $idHash = substr($idHash.$idHash, 0, 13); // 13 bytes length

            $stat = $model->getEmailStatus($idHash);

            // stat doesn't exist, create one
            if ($stat === null) {
                $lead['email']=$email; // needed for stat
                $this->addStat($lead, $email, $query, $idHash);
            } else { // Prevent marking the email as read on creation
                $model->hitEmail($idHash, $this->request); // add email event
            }
        }
    }

    /**
     * @return Response
     */
    public function trackingImageAction()
    {

        $this->doTracking();
        return TrackingPixelHelper::getResponse($this->request); // send gif
    }

    /**
     * @param $lead
     * @param $email
     * @param $query
     * @param $idHash
     */
    public function addStat($lead, $email, $query, $idHash)
    {
        if ($lead !== null) {
            $mailer = $this->factory->getMailer();

            // To lead
            $mailer->addTo($email);

            // sanitize variables to prevent malicious content
            $from = filter_var($query['from'], FILTER_SANITIZE_EMAIL);
            $mailer->setFrom($from, '');

            // Set Content
            $body = filter_var($query['body'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            $mailer->setBody($body);
            $mailer->parsePlainText($body);

            // Set lead
            $mailer->setLead($lead);
            $mailer->setIdHash($idHash);

            $subject = filter_var($query['subject'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
            $mailer->setSubject($subject);
            $mailer->createEmailStat();
        }
    }

    /**
     * @param $email
     * @param $repo
     * @return mixed
     */
    public function createLead($email, $repo)
    {
        $model = $this->getModel('lead.lead');
        $lead = $model->getEntity();
        // set custom field values
        $data = ['email' => $email];
        $model->setFieldValues($lead, $data, true);
        // create lead
        $model->saveEntity($lead);

        // return entity
        return $repo->getLeadByEmail($email);
    }

}
