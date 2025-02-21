<?php

namespace Mautic\FormBundle\EventListener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\Form\Type\SubmitActionEmailType;
use Mautic\FormBundle\Form\Type\SubmitActionRepostType;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormSubscriber implements EventSubscriberInterface
{
    private MailHelper $mailer;

    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        MailHelper $mailer,
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private LanguageHelper $languageHelper,
    ) {
        $this->mailer = $mailer->getMailer();
    }

    public static function getSubscribedEvents(): array
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
     */
    public function onFormPostSave(Events\FormEvent $event): void
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
        if (!array_key_exists($form->getLanguage(), $this->languageHelper->getSupportedLanguages())) {
            $this->languageHelper->extractLanguagePackage($form->getLanguage());
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onFormDelete(Events\FormEvent $event): void
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
     */
    public function onFormBuilder(Events\FormBuilderEvent $event): void
    {
        $event->addSubmitAction('form.email', [
            'group'              => 'mautic.email.actions',
            'label'              => 'mautic.form.action.sendemail',
            'description'        => 'mautic.form.action.sendemail.descr',
            'formType'           => SubmitActionEmailType::class,
            'formTheme'          => '@MauticForm/FormTheme/FormAction/_formaction_properties_row.html.twig',
            'formTypeCleanMasks' => [
                'message' => 'raw',
            ],
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
            'template'          => '@MauticForm/Action/form_email.html.twig',
        ]);

        $event->addSubmitAction('form.repost', [
            'group'              => 'mautic.form.actions',
            'label'              => 'mautic.form.action.repost',
            'description'        => 'mautic.form.action.repost.descr',
            'formType'           => SubmitActionRepostType::class,
            'formTheme'          => '@MauticForm/FormTheme/SubmitAction/_submit_action_repost_widget.html.twig',
            'formTypeCleanMasks' => [
                'post_url'             => 'url',
                'failure_email'        => 'string',
                'authorization_header' => 'string',
            ],
            'eventName'         => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm' => true,
        ]);
    }

    public function onFormSubmitActionSendEmail(Events\SubmissionEvent $event): void
    {
        if (!$event->checkContext('form.email')) {
            return;
        }

        // replace line brakes with <br> for textarea values
        if ($tokens = $event->getTokens()) {
            foreach ($tokens as &$value) {
                $value = nl2br(html_entity_decode($value, ENT_QUOTES));
            }
            unset($value);
        }

        $config    = $event->getActionConfig();
        $lead      = $event->getSubmission()->getLead();
        $leadEmail = null !== $lead ? $lead->getEmail() : null;
        $ccEmails  = $bccEmails = [];
        $emails    = $this->getEmailsFromString($config['to']);

        if (isset($config['cc']) && '' !== $config['cc']) {
            $ccEmails = $this->getEmailsFromString($config['cc']);
            unset($config['cc']);
        }

        if (isset($config['bcc']) && '' !== $config['bcc']) {
            $bccEmails = $this->getEmailsFromString($config['bcc']);
            unset($config['bcc']);
        }

        if (count($emails) > 0 || count($ccEmails) > 0 || count($bccEmails) > 0) {
            $this->setMailer($config, $tokens, $emails, $lead);

            // Check for !isset to keep BC to existing behavior prior to 2.13.0
            if ((!isset($config['set_replyto']) || !empty($config['set_replyto'])) && !empty($leadEmail)) {
                // Reply to lead for user convenience
                $this->mailer->setReplyTo($leadEmail);
            }

            if (count($ccEmails) > 0) {
                $this->mailer->setCc($ccEmails);
            }

            if (count($bccEmails) > 0) {
                $this->mailer->setBcc($bccEmails);
            }

            $this->mailer->send(true);
        }

        if ($config['copy_lead'] && !empty($leadEmail)) {
            // Send copy to lead
            $this->setMailer($config, $tokens, [$leadEmail => null], $lead, false);

            $this->mailer->send(true);
        }

        $owner = null !== $lead ? $lead->getOwner() : null;
        if (!empty($config['email_to_owner']) && $config['email_to_owner'] && null !== $owner) {
            // Send copy to owner
            $this->setMailer($config, $tokens, [$owner->getEmail() => null], $lead);

            $this->mailer->send(true);
        }
    }

    public function onFormSubmitActionRepost(Events\SubmissionEvent $event): void
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
            $value               = $results[$field['alias']] ?? $post[$field['alias']];
            $matchedFields[$key] = $field['alias'];

            // decode html chars and quotes before posting to next form
            $payload[$key]       = html_entity_decode(htmlspecialchars_decode($value, ENT_QUOTES), ENT_QUOTES);
        }

        $event->setPostSubmitPayload($payload);

        $headers = [
            'X-Forwarded-For' => $event->getSubmission()->getIpAddress()->getIpAddress(),
        ];

        if (!empty($config['authorization_header'])) {
            if (str_contains($config['authorization_header'], ':')) {
                [$key, $value] = explode(':', $config['authorization_header']);
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
        } catch (ClientException|ServerException $exception) {
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
        } else {
            parse_str($body, $output);
            if ($output) {
                $body = $output;
            }
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

    private function postToHtml($post): string
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

        return $output.'</table>';
    }

    /**
     * @return array<string, null>
     */
    private function getEmailsFromString($emailString): array
    {
        return (!empty($emailString)) ? array_fill_keys(array_map('trim', explode(',', $emailString)), null) : [];
    }

    /**
     * @param array<mixed>               $config
     * @param array<mixed>               $tokens
     * @param array<string, string|null> $to
     */
    private function setMailer(array $config, array $tokens, array $to, Lead $lead = null, bool $internalSend = true): void
    {
        $this->mailer->reset();

        if (count($to)) {
            $this->mailer->setTo($to);
        }

        $this->mailer->setSubject($config['subject']);
        $this->mailer->addTokens($tokens);
        $this->mailer->setBody($config['message']);
        $this->mailer->parsePlainText($config['message']);

        if ($lead) {
            $this->mailer->setLead($lead->getProfileFields(), $internalSend);
        }
    }
}
