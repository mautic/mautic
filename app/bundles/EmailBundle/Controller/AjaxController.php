<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Symfony\Component\HttpFoundation\Request;
use Mautic\EmailBundle\Swiftmailer\Transport\AmazonTransport;
use Mautic\EmailBundle\Swiftmailer\Transport\MandrillTransport;
use Mautic\EmailBundle\Swiftmailer\Transport\PostmarkTransport;
use Mautic\EmailBundle\Swiftmailer\Transport\SendgridTransport;

/**
 * Class AjaxController
 *
 * @package Mautic\EmailBundle\Controller
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAbTestFormAction(Request $request)
    {
        $dataArray = array(
            'success' => 0,
            'html'    => ''
        );
        $type      = InputHelper::clean($request->request->get('abKey'));
        $emailId   = InputHelper::int($request->request->get('emailId'));

        if (!empty($type)) {
            //get the HTML for the form
            /** @var \Mautic\EmailBundle\Model\EmailModel $model */
            $model = $this->getModel('email');

            $email = $model->getEntity($emailId);

            $abTestComponents = $model->getBuilderComponents($email, 'abTestWinnerCriteria');
            $abTestSettings   = $abTestComponents['criteria'];

            if (isset($abTestSettings[$type])) {
                $html     = '';
                $formType = (!empty($abTestSettings[$type]['formType'])) ? $abTestSettings[$type]['formType'] : '';
                if (!empty($formType)) {
                    $formOptions = (!empty($abTestSettings[$type]['formTypeOptions'])) ? $abTestSettings[$type]['formTypeOptions'] : array();
                    $form        = $this->get('form.factory')->create(
                        'email_abtest_settings',
                        array(),
                        array('formType' => $formType, 'formTypeOptions' => $formOptions)
                    );
                    $html        = $this->renderView(
                        'MauticEmailBundle:AbTest:form.html.php',
                        array(
                            'form' => $this->setFormTheme($form, 'MauticEmailBundle:AbTest:form.html.php', 'MauticEmailBundle:FormTheme\Email')
                        )
                    );
                }

                $html                 = str_replace(
                    array(
                        'email_abtest_settings[',
                        'email_abtest_settings_',
                        'email_abtest_settings'
                    ),
                    array(
                        'emailform[variantSettings][',
                        'emailform_variantSettings_',
                        'emailform'
                    ),
                    $html
                );
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function sendBatchAction(Request $request)
    {
        $dataArray = array('success' => 0);

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->getModel('email');
        $objectId = $request->request->get('id', 0);
        $pending  = $request->request->get('pending', 0);
        $limit    = $request->request->get('batchlimit', 100);

        if ($objectId && $entity = $model->getEntity($objectId)) {
            $dataArray['success'] = 1;
            $session              = $this->factory->getSession();
            $progress             = $session->get('mautic.email.send.progress', array(0, (int) $pending));
            $stats                = $session->get('mautic.email.send.stats', array('sent' => 0, 'failed' => 0, 'failedRecipients' => array()));

            if ($pending && !$inProgress = $session->get('mautic.email.send.active', false)) {
                $session->set('mautic.email.send.active', true);
                list($batchSentCount, $batchFailedCount, $batchFailedRecipients) = $model->sendEmailToLists($entity, null, $limit);

                $progress[0] += ($batchSentCount + $batchFailedCount);
                $stats['sent'] += $batchSentCount;
                $stats['failed'] += $batchFailedCount;

                foreach ($batchFailedRecipients as $list => $emails) {
                    $stats['failedRecipients'] = $stats['failedRecipients'] + $emails;
                }

                $session->set('mautic.email.send.progress', $progress);
                $session->set('mautic.email.send.stats', $stats);
                $session->set('mautic.email.send.active', false);
            }

            $dataArray['percent'] = ($progress[1]) ? ceil(($progress[0] / $progress[1]) * 100) : 100;

            $dataArray['progress'] = $progress;
            $dataArray['stats']    = $stats;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Called by parent::getBuilderTokensAction()
     *
     * @param $query
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        return $model->getBuilderComponents(null, array('tokens'), $query, false);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function generatePlaintTextAction(Request $request)
    {
        $custom    = $request->request->get('custom');
        $id        = $request->request->get('id');

        $parser = new PlainTextHelper(
            array(
                'base_url' => $request->getSchemeAndHttpHost().$request->getBasePath()
            )
        );

        // Convert placeholders into raw tokens
        BuilderTokenHelper::replaceVisualPlaceholdersWithTokens($custom);

        $dataArray = [
            'text' => $parser->setHtml($custom)->getText()
        ];

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAttachmentsSizeAction(Request $request)
    {
        $assets = $request->get('assets', array(), true);
        $size   = 0;
        if ($assets) {
            /** @var \Mautic\AssetBundle\Model\AssetModel $assetModel */
            $assetModel = $this->getModel('asset');
            $size       = $assetModel->getTotalFilesize($assets);
        }

        return $this->sendJsonResponse(array('size' => $size));
    }

    /**
     * Tests monitored email connection settings
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function testMonitoredEmailServerConnectionAction(Request $request)
    {
        $dataArray = array('success' => 0, 'message' => '');

        if ($this->factory->getUser()->isAdmin()) {
            $settings = $request->request->all();

            if (empty($settings['password'])) {
                $existingMonitoredSettings = $this->factory->getParameter('monitored_email');
                if (is_array($existingMonitoredSettings) && (!empty($existingMonitoredSettings[$settings['mailbox']]['password']))) {
                    $settings['password'] = $existingMonitoredSettings[$settings['mailbox']]['password'];
                }
            }

            /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $helper */
            $helper = $this->factory->getHelper('mailbox');

            try {
                $helper->setMailboxSettings($settings, false);
                $folders = $helper->getListingFolders('');
                if (!empty($folders)) {
                    $dataArray['folders'] = '';
                    foreach ($folders as $folder) {
                        $dataArray['folders'] .= "<option value=\"$folder\">$folder</option>\n";
                    }
                }
                $dataArray['success'] = 1;
                $dataArray['message'] = $this->factory->getTranslator()->trans('mautic.core.success');
            } catch (\Exception $e) {
                $dataArray['message'] = $e->getMessage();
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Tests mail transport settings
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function testEmailServerConnectionAction(Request $request)
    {
        $dataArray = array('success' => 0, 'message' => '');

        if ($this->factory->getUser()->isAdmin()) {
            $settings = $request->request->all();

            $transport = $settings['transport'];

            switch($transport) {
                case 'gmail':
                    $mailer = new \Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl');
                    break;
                case 'smtp':
                    $mailer = new \Swift_SmtpTransport($settings['host'], $settings['port'], $settings['encryption']);
                    break;
                case 'mautic.transport.amazon':
                    $mailer = new AmazonTransport($settings['amazon_region']);
                    break;
                default:
                    if ($this->container->has($transport)) {
                        $mailer = $this->container->get($transport);
                    }
            }

            if (method_exists($mailer, 'setMauticFactory')) {
                $mailer->setMauticFactory($this->factory);
            }

            if (!empty($mailer)) {
                if (empty($settings['password'])) {
                    $settings['password'] = $this->factory->getParameter('mailer_password');
                }
                $mailer->setUsername($settings['user']);
                $mailer->setPassword($settings['password']);

                $logger = new \Swift_Plugins_Loggers_ArrayLogger();
                $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

                try {
                    $mailer->start();
                    $translator = $this->factory->getTranslator();

                    if ($settings['send_test'] == 'true') {
                        $message = new \Swift_Message(
                            $translator->trans('mautic.email.config.mailer.transport.test_send.subject'),
                            $translator->trans('mautic.email.config.mailer.transport.test_send.body')
                        );

                        $user = $this->factory->getUser();

                        $message->setFrom(array($settings['from_email'] => $settings['from_name']));
                        $message->setTo(array($user->getEmail() => $user->getFirstName().' '.$user->getLastName()));

                        $mailer->send($message);
                    }

                    $dataArray['success'] = 1;
                    $dataArray['message'] = $translator->trans('mautic.core.success');

                } catch (\Exception $e) {
                    $dataArray['message'] = $e->getMessage() . '<br />' . $logger->dump();
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

}