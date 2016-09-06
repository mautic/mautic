<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Joomla\Http\Http;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\Exception\ValidationException;
use Mautic\FormBundle\Form\Type\SubmitActionRepostType;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var MailHelper
     */
    private $mailer;

    /**
     * @var Http
     */
    private $connector;

    /**
     * FormSubscriber constructor.
     *
     * @param MauticFactory $factory
     * @param MailHelper    $mailer
     */
    public function __construct(MauticFactory $factory, MailHelper $mailer, Http $connector)
    {
        parent::__construct($factory);

        $this->mailer    = $mailer->getMailer();
        $this->connector = $connector;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_POST_SAVE           => ['onFormPostSave', 0],
            FormEvents::FORM_POST_DELETE         => ['onFormDelete', 0],
            FormEvents::FORM_ON_BUILD            => ['onFormBuilder', 0],
            FormEvents::ON_EXECUTE_SUBMIT_ACTION => [
                ['onFormSubmitActionSendEmail', 0],
                ['onFormSubmitActionRepost', 0],
            ]
        ];
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();
        if ($details = $event->getChanges()) {
            $log = [
                "bundle"    => "form",
                "object"    => "form",
                "objectId"  => $form->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->factory->getIpAddressFromRequest()
            ];
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form = $event->getForm();
        $log  = [
            "bundle"    => "form",
            "object"    => "form",
            "objectId"  => $form->deletedId,
            "action"    => "delete",
            "details"   => ['name' => $form->getName()],
            "ipAddress" => $this->factory->getIpAddressFromRequest()
        ];
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Add a simple email form
     *
     * @param FormBuilderEvent $event
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
                'message' => 'html'
            ],
            'eventName'          => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm'  => true,
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
                'failure_email'        => 'email',
                'authorization_header' => 'string'
            ],
            'eventName'          => FormEvents::ON_EXECUTE_SUBMIT_ACTION,
            'allowCampaignForm'  => true,
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
        $leadEmail = $lead->getEmail();
        $emails    = (!empty($config['to'])) ? array_fill_keys(explode(',', $config['to']), null) : [];

        if (!empty($emails)) {
            $this->mailer->setTo($emails);

            if (!empty($leadEmail)) {
                // Reply to lead for user convenience
                $this->mailer->setReplyTo($leadEmail);
            }

            if (!empty($config['cc'])) {
                $emails = array_fill_keys(explode(',', $config['cc']), null);
                $this->mailer->setCc($emails);
            }

            if (!empty($config['bcc'])) {
                $emails = array_fill_keys(explode(',', $config['bcc']), null);
                $this->mailer->setBcc($emails);
            }

            $this->mailer->setSubject($config['subject']);

            $this->mailer->addTokens($tokens);
            $this->mailer->setBody($config['message']);
            $this->mailer->parsePlainText($config['message']);

            $this->mailer->send(true);
        }

        if ($config['copy_lead'] && !empty($leadEmail)) {
            // Send copy to lead
            $this->mailer->reset();
            $this->mailer->setLead($lead->getProfileFields());
            $this->mailer->setTo($leadEmail);
            $this->mailer->setSubject($config['subject']);
            $this->mailer->addTokens($tokens);
            $this->mailer->setBody($config['message']);
            $this->mailer->parsePlainText($config['message']);

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

        $post    = $event->getPost();
        $results = $event->getResults();
        $config  = $event->getActionConfig();
        $fields  = $event->getFields();
        $lead    = $event->getSubmission()->getLead();

        $payload = [
            'mautic_contact' => $lead->getProfileFields()
        ];

        foreach ($fields as $field) {
            if (!isset($post[$field['alias']]) || 'button' == $field['type']) {
                continue;
            }

            $key = (!empty($config[$field['alias']])) ? $config[$field['alias']] : $field['alias'];

            // Use the cleaned value by default - but if set to not save result, get from post
            $value = (isset($results[$field['alias']])) ? $results[$field['alias']] : $post[$field['alias']];

            $payload[$key] = $value;
        }

        $headers = [
            'X-Forwarded-For' => $event->getSubmission()->getIpAddress()->getIpAddress()
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
            $response = $this->connector->post($config['post_url'], $payload, $headers, 10);

            $error = false;
            $violations = [];

            if ($json = json_decode($response->body, true)) {
                if (isset($json['error'])) {
                    $error = $json['error'];
                } elseif (isset($json['errors'])) {
                    $error = implode(', ', $json['errors']);
                } elseif (isset($json['violations'])) {
                    $error      = $this->translator->trans('mautic.form.action.repost.validation_failed');
                    $violations = $json['violations'];
                } elseif (200 !== $response->code) {
                    $error = $response->body;
                }
            } elseif (200 !== $response->code) {
                $error = $response->body;
            }

            if ($error || $violations) {
                $exception = (new ValidationException($error))
                    ->setViolations($violations);

                throw $exception;
            }
        } catch (\Exception $exception) {
            if ($exception instanceof ValidationException) {
                if ($violations = $exception->getViolations()) {
                    throw $exception;
                }
            }

            $email = trim($config['failure_email']);
            // Failed so send email if applicable
            if (!empty($email)) {
                $post['array'] = $post;
                $results    = $this->postToHtml($post);
                $submission = $event->getSubmission();
                $this->mailer->addTo($email);
                $this->mailer->setSubject(
                    $this->translator->trans('mautic.form.action.repost.failed_subject', ['%form%' => $submission->getForm()->getName()])
                );
                $this->mailer->setBody(
                    $this->translator->trans(
                        'mautic.form.action.repost.failed_message',
                        [
                            '%link%'    => $this->router->generate(
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
            $output .= "</td></tr>";
        }
        $output .= '</table>';

        return $output;
    }
}
