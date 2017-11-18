<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\Form\Type\SubmitActionRepostType;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var MailHelper
     */
    protected $mailer;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * FormSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     * @param MailHelper     $mailer
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel, MailHelper $mailer, CoreParametersHelper $coreParametersHelper)
    {
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->auditLogModel        = $auditLogModel;
        $this->mailer               = $mailer->getMailer();
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_POST_SAVE           => ['onFormPostSave', 0],
            FormEvents::FORM_POST_DELETE         => ['onFormDelete', 0],
            FormEvents::FORM_ON_BUILD            => ['onFormBuilder', 0],
            FormEvents::ON_EXECUTE_SUBMIT_ACTION => [
                ['onFormSubmitActionSendEmail', 0],
                ['onFormSubmitActionRepost', 0],
            ],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'form',
                'object'    => 'form',
                'objectId'  => $form->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form = $event->getForm();
        $log  = [
            'bundle'    => 'form',
            'object'    => 'form',
            'objectId'  => $form->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $form->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a simple email form.
     *
     * @param Events\FormBuilderEvent $event
     */
    public function onFormBuilder(Events\FormBuilderEvent $event)
    {
        $action = [
            'group'              => 'mautic.email.actions',
            'label'              => 'mautic.form.action.sendemail',
            'description'        => 'mautic.form.action.sendemail.descr',
            'formType'           => 'form_submitaction_sendemail',
            'formTheme'          => 'MauticFormBundle:FormTheme\SubmitAction',
            'formTypeCleanMasks' => [
                'message' => 'html',
            ],
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ];

        $event->addSubmitAction('form.email', $action);

        $action = [
            'group'              => 'mautic.form.actions',
            'label'              => 'mautic.form.action.repost',
            'description'        => 'mautic.form.action.repost.descr',
            'formType'           => SubmitActionRepostType::class,
            'formTheme'          => 'MauticFormBundle:FormTheme\SubmitAction',
            'formTypeCleanMasks' => [
                'post_url'             => 'url',
                'failure_email'        => 'string',
                'authorization_header' => 'string',
            ],
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ];

        $event->addSubmitAction('form.repost', $action);
    }

    /**
     * @param Events\SubmissionEvent $event
     */
    public function onFormSubmitActionSendEmail(Events\SubmissionEvent $event)
    {
        if (!$event->checkContext('form.email')) {
            return;
        }

        // replace line brakes with <br> for textarea values
        if ($tokens = $event->getTokens()) {
            foreach ($tokens as $token => &$value) {
                $value = nl2br(html_entity_decode($value));
            }
        }

        $config    = $event->getActionConfig();
        $lead      = $event->getSubmission()->getLead();
        $leadEmail = $lead !== null ? $lead->getEmail() : null;
        $emails    = $this->getEmailsFromString($config['to']);

        if (!empty($emails)) {
            $this->setMailer($config, $tokens, $emails);

            if (!empty($leadEmail)) {
                // Reply to lead for user convenience
                $this->mailer->setReplyTo($leadEmail);
            }

            if (!empty($config['cc'])) {
                $emails = $this->getEmailsFromString($config['cc']);
                $this->mailer->setCc($emails);
            }

            if (!empty($config['bcc'])) {
                $emails = $this->getEmailsFromString($config['bcc']);
                $this->mailer->setBcc($emails);
            }

            $this->mailer->send(true);
        }

        if ($config['copy_lead'] && !empty($leadEmail)) {
            // Send copy to lead
            $this->setMailer($config, $tokens, $leadEmail);

            $this->mailer->setLead($lead->getProfileFields());

            $this->mailer->send(true);
        }

        $owner = $lead !== null ? $lead->getOwner() : null;
        if (!empty($config['email_to_owner']) && $config['email_to_owner'] && null !== $owner) {
            // Send copy to owner
            $this->setMailer($config, $tokens, $owner->getEmail());

            $this->mailer->setLead($lead->getProfileFields());

            $this->mailer->send(true);
        }
    }

    /**
     * @param Events\SubmissionEvent $event
     */
    public function onFormSubmitActionRepost(Events\SubmissionEvent $event)
    {
        if (!$event->checkContext('form.repost')) {
            return;
        }

        $post          = $event->getPost();
        $results       = $event->getResults();
        $config        = $event->getActionConfig();
        $fields        = $event->getFields();
        $lead          = $event->getSubmission()->getLead();
        $matchedFields = [];
        $payload       = [
            'mautic_contact' => $lead->getProfileFields(),
            'mautic_form'    => [
                'id'   => $post['formId'],
                'name' => $post['formName'],
                'url'  => $post['return'],
            ],
        ];
        $fieldTypes = [];
        foreach ($fields as $field) {
            $fieldTypes[$field['alias']] = $field['type'];
            if (!isset($post[$field['alias']]) || 'button' == $field['type']) {
                continue;
            }

            $key = (!empty($config[$field['alias']])) ? $config[$field['alias']] : $field['alias'];

            // Use the cleaned value by default - but if set to not save result, get from post
            $value               = (isset($results[$field['alias']])) ? $results[$field['alias']] : $post[$field['alias']];
            $matchedFields[$key] = $field['alias'];
            $payload[$key]       = $value;
        }

        $headers = [
            'X-Forwarded-For' => $event->getSubmission()->getIpAddress()->getIpAddress(),
        ];

        if (!empty($config['authorization_header'])) {
            if (strpos($config['authorization_header'], ':') !== false) {
                list($key, $value) = explode(':', $config['authorization_header']);
            } else {
                $key   = 'Authorization';
                $value = $config['authorization_header'];
            }
            $headers[trim($key)] = trim($value);
        }

        try {
            $client   = new Client(['timeout' => 15]);
            $response = $client->post(
                $config['post_url'],
                [
                    'form_params' => $payload,
                    'headers'     => $headers,
                ]
            );

            if ($redirect = $this->parseResponse($response, $matchedFields)) {
                $event->setPostSubmitCallbackResponse('form.repost', new RedirectResponse($redirect));
            }
        } catch (ServerException $exception) {
            $this->parseResponse($exception->getResponse(), $matchedFields);
        } catch (\Exception $exception) {
            if ($exception instanceof ValidationException) {
                if ($violations = $exception->getViolations()) {
                    throw $exception;
                }
            }

            $email = $config['failure_email'];
            // Failed so send email if applicable
            if (!empty($email)) {
                // Remove Mautic values and password fields
                foreach ($post as $key => $value) {
                    if (in_array($key, ['messenger', 'submit', 'formId', 'formid', 'formName', 'return'])) {
                        unset($post[$key]);
                    }
                    if (isset($fieldTypes[$key]) && in_array($fieldTypes[$key], ['password'])) {
                        $post[$key] = '*********';
                    }
                }
                $post['mautic_contact'] = array_filter($payload['mautic_contact']);
                $post['mautic_form']    = $payload['mautic_form'];

                $results    = $this->postToHtml($post);
                $submission = $event->getSubmission();
                $emails     = $emails     = $this->getEmailsFromString($email);
                $this->mailer->setTo($emails);
                $this->mailer->setSubject(
                    $this->translator->trans('mautic.form.action.repost.failed_subject', ['%form%' => $submission->getForm()->getName()])
                );
                $this->mailer->setBody(
                    $this->translator->trans(
                        'mautic.form.action.repost.failed_message',
                        [
                            '%link%' => $this->router->generate(
                                'mautic_form_results',
                                ['objectId' => $submission->getForm()->getId(), 'result' => $submission->getId()],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            '%message%' => $exception->getMessage(),
                            '%results%' => $results,
                        ]
                    )
                );
                $this->mailer->parsePlainText();

                $this->mailer->send();
            }
        }
    }

    /**
     * @param Response $response
     * @param array    $matchedFields
     *
     * @return bool|mixed
     */
    private function parseResponse(Response $response, array $matchedFields = [])
    {
        $body       = (string) $response->getBody();
        $error      = false;
        $redirect   = false;
        $violations = [];

        if ($json = json_decode($body, true)) {
            $body = $json;
        } elseif ($params = parse_str($body)) {
            $body = $params;
        }

        if (is_array($body)) {
            if (isset($body['error'])) {
                $error = $body['error'];
            } elseif (isset($body['errors'])) {
                $error = implode(', ', $body['errors']);
            } elseif (isset($body['violations'])) {
                $error          = $this->translator->trans('mautic.form.action.repost.validation_failed');
                $formViolations = $body['violations'];

                // Ensure the violations match up to Mautic's
                $violations = [];
                foreach ($formViolations as $field => $violation) {
                    if (isset($matchedFields[$field])) {
                        $violations[$matchedFields[$field]] = $violation;
                    } else {
                        $error .= ' '.$violation;
                    }
                }
            } elseif (isset($body['redirect'])) {
                $redirect = $body['redirect'];
            }
        }

        if (!$error && 200 !== $response->getStatusCode()) {
            $error = (string) $response->getBody();
        }

        if ($error || $violations) {
            $exception = (new ValidationException($error))
                ->setViolations($violations);

            throw $exception;
        }

        return $redirect;
    }

    /**
     * @param $post
     *
     * @return string
     */
    private function postToHtml($post)
    {
        $output = '<table>';
        foreach ($post as $key => $row) {
            $output .= "<tr><td style='vertical-align: top'><strong>$key</strong></td><td>";
            if (is_array($row)) {
                $output .= $this->postToHtml($row);
            } else {
                $output .= $row;
            }
            $output .= '</td></tr>';
        }
        $output .= '</table>';

        return $output;
    }

    /**
     * @param $emailString
     *
     * @return array
     */
    private function getEmailsFromString($emailString)
    {
        return (!empty($emailString)) ? array_fill_keys(array_map('trim', explode(',', $emailString)), null) : [];
    }

    /**
     * @param array $config
     * @param array $tokens
     * @param       $to
     */
    private function setMailer(array $config, array $tokens, $to)
    {
        $this->mailer->reset();

        // ingore queue
        if ($this->coreParametersHelper->getParameter('mailer_spool_type') == 'file' && $config['immediately']) {
            $this->mailer = $this->mailer->getSampleMailer();
        }

        $this->mailer->setTo($to);
        $this->mailer->setSubject($config['subject']);
        $this->mailer->addTokens($tokens);
        $this->mailer->setBody($config['message']);
        $this->mailer->parsePlainText($config['message']);
    }
}
