<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOutlookBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{

    /**
     * @return Response
     */
    public function trackingImageAction()
    {
        $logger = $this->factory->getLogger();

        // if additional data were sent with the tracking pixel
        if ($this->request->server->get('QUERY_STRING')) {
            parse_str($this->request->server->get('QUERY_STRING'), $query);

            // URL attr 'd' is encoded so let's decode it first.
            if (isset($query['d'], $query['sig'])) {
                // get secret from Outlook plugin settings
                $integrationHelper = $this->factory->getHelper('integration');
                $outlookIntegration = $integrationHelper->getIntegrationObject('Outlook');
                $keys = $outlookIntegration->getDecryptedApiKeys();

                // generate signature
                $salt = $keys['secret'];

                // add MD5 prefix
                if (strpos($salt, '$1$') === FALSE)
                    $salt = '$1$'.$salt;

                $cr = crypt(urlencode($query['d']), $salt);

                $mySig = hash('crc32b', $cr); // this hash is used in c#

                // compare signatures
                if (hash_equals($mySig, $query['sig'])) {
                    // decode and parse query variables
                    $b64 = base64_decode($query['d']);
                    $gz = gzdecode($b64);
                    parse_str($gz, $query);
                } else {
                    // signatures don't match: stop
                    unset($query);
                }

                if (!empty($query) && isset($query['email'], $query['subject'], $query['body'])) {

                    /** @var \Mautic\EmailBundle\Model\EmailModel $model */
                    $model = $this->getModel('email');

                    $idHash = hash('crc32', $query['body']);
                    $idHash = substr($idHash.$idHash, 0, 13); // 13 bytes length

                    $stat = $model->getEmailStatus($idHash);

                    // sytat doesn't exist, create one
                    if ($stat === null) {

                        // email is a semicolon delimited list of emails
                        $emails = explode(';', $query['email']);

                        $lead = null;
                        $to = '';

                        foreach ($emails as $email) {
                            $lead = $this->getModel('lead')->getRepository()->getLeadByEmail($email);
                            if ($lead !== null) {
                                $to = $email;
                                break;
                            }
                        }

                        if ($lead !== null) {
                            $mailer = $this->factory->getMailer();

                            // To lead
                            $mailer->addTo($to);

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

                    $model->hitEmail($idHash, $this->request);
                }
            }
        }

        return TrackingPixelHelper::getResponse($this->request);
    }

}
