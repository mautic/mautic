<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Controller\FrequencyRuleTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\EventListener\BuilderSubscriber;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class PublicController extends CommonFormController
{
    use FrequencyRuleTrait;

    /**
     * @return Response
     */
    public function indexAction(Request $request, AnalyticsHelper $analyticsHelper, $idHash)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');
        $stat  = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            if ($this->security->isAnonymous()) {
                $model->hitEmail($stat, $request, true);
            }

            $tokens = $stat->getTokens();
            if (is_array($tokens)) {
                // Override tracking_pixel so as to not cause a double hit
                $tokens['{tracking_pixel}'] = MailHelper::getBlankPixel();
            }

            if ($copy = $stat->getStoredCopy()) {
                $subject = $copy->getSubject();
                $content = $copy->getBody();

                // Convert emoji
                $content = EmojiHelper::toEmoji($content, 'short');
                $subject = EmojiHelper::toEmoji($subject, 'short');

                // Replace tokens
                $content = str_ireplace(array_keys($tokens), $tokens, $content);
                $subject = str_ireplace(array_keys($tokens), $tokens, $subject);
            } else {
                $subject = '';
                $content = '';
            }

            $content = $analyticsHelper->addCode($content);

            // Add subject as title
            if (!empty($subject)) {
                if (str_contains($content, '<title></title>')) {
                    $content = str_replace('<title></title>', "<title>$subject</title>", $content);
                } elseif (!str_contains($content, '<title>')) {
                    $content = str_replace('<head>', "<head>\n<title>$subject</title>", $content);
                }
            }

            return new Response($content);
        }

        return $this->notFound();
    }

    public function trackingImageAction(
        Request $request,
        MessageBusInterface $messageBus,
        LoggerInterface $logger,
        string $idHash
    ): Response {
        try {
            $messageBus->dispatch(new EmailHitNotification($idHash, $request));
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage(), ['idHash' => $idHash]);
            $emailModel = $this->getModel('email');
            assert($emailModel instanceof EmailModel);

            $emailModel->hitEmail($idHash, $request);
        }

        return TrackingPixelHelper::getResponse($request);
    }

    /**
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function unsubscribeAction(
        Request $request,
        ContactTracker $contactTracker,
        $idHash
    ) {
        // Find the email
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model                 = $this->getModel('email');
        $translator            = $this->translator;
        $stat                  = $model->getEmailStatus($idHash);
        $message               = '';
        $email                 = null;
        $lead                  = null;
        $template              = null;
        $session               = $request->getSession();
        $isOneClickUnsubscribe = $request->isMethod(Request::METHOD_POST) && 'One-Click' === $request->get('List-Unsubscribe');

        if (!empty($stat)) {
            if ($isOneClickUnsubscribe) {
                // RFC 8058 One-Click unsubscribe
                $unsubscribeComment = $this->translator->trans('mautic.email.dnc.unsubscribed');
                $model->setDoNotContact($stat, $unsubscribeComment, DoNotContact::UNSUBSCRIBED);

                return new Response($this->translator->trans('mautic.lead.do.not.contact_unsubscribed'));
            }

            if ($email = $stat->getEmail()) {
                $template = $email->getTemplate();
                if ('mautic_code_mode' === $template) {
                    // Use system default
                    $template = null;
                }

                /** @var \Mautic\FormBundle\Entity\Form $unsubscribeForm */
                $unsubscribeForm = $email->getUnsubscribeForm();
                if (null != $unsubscribeForm && $unsubscribeForm->isPublished()) {
                    $formTemplate = $unsubscribeForm->getTemplate();
                    $formModel    = $this->getModel('form');
                    \assert($formModel instanceof FormModel);
                    $formContent = '<div class="mautic-unsubscribeform">'.$formModel->getContent($unsubscribeForm).'</div>';
                }
            }
        } else {
            if ($isOneClickUnsubscribe) {
                return new Response($this->translator->trans('mautic.email.stat_record.not_found'), Response::HTTP_NOT_FOUND);
            }
        }

        if (empty($template) && empty($formTemplate)) {
            $template = $this->coreParametersHelper->get('theme');
        } elseif (!empty($formTemplate)) {
            $template = $formTemplate;
        }

        $theme = $this->factory->getTheme($template);
        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }
        $contentTemplate = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/message.html.twig');
        if (!empty($stat)) {
            $successSessionName = 'mautic.email.prefscenter.success';

            if ($lead = $stat->getLead()) {
                // Set the lead as current lead
                $contactTracker->setTrackedContact($lead);

                // Set lead lang
                if ($lead->getPreferredLocale()) {
                    $translator->setLocale($lead->getPreferredLocale());
                }

                // Add contact ID to the session name in case more contacts
                // share the same session/device and the contact is known.
                $successSessionName .= ".{$lead->getId()}";
            }

            if (!$this->coreParametersHelper->get('show_contact_preferences')) {
                $message = $this->getUnsubscribeMessage($idHash, $model, $stat, $translator);
            } elseif ($lead) {
                $action = $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]);

                $viewParameters = [
                    'lead'                         => $lead,
                    'idHash'                       => $idHash,
                    'showContactFrequency'         => $this->coreParametersHelper->get('show_contact_frequency'),
                    'showContactPauseDates'        => $this->coreParametersHelper->get('show_contact_pause_dates'),
                    'showContactPreferredChannels' => $this->coreParametersHelper->get('show_contact_preferred_channels'),
                    'showContactCategories'        => $this->coreParametersHelper->get('show_contact_categories'),
                    'showContactSegments'          => $this->coreParametersHelper->get('show_contact_segments'),
                ];

                if ($session->get($successSessionName)) {
                    $viewParameters['successMessage'] = $this->translator->trans('mautic.email.preferences_center_success_message.text');
                }

                $form = $this->getFrequencyRuleForm($lead, $viewParameters, $data, true, $action, true);
                if (true === $form) {
                    $session->set($successSessionName, 1);

                    return $this->postActionRedirect(
                        [
                            'returnUrl'       => $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]),
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $contentTemplate,
                        ]
                    );
                } else {
                    // success message should not persist on page refresh
                    $session->set($successSessionName, 0);
                }

                $formView = $form->createView();
                /** @var Page $prefCenter */
                if ($email && ($prefCenter = $email->getPreferenceCenter()) && $prefCenter->getIsPreferenceCenter()) {
                    $html = $prefCenter->getCustomHtml();
                    // check if tokens are present
                    if (str_contains($html, 'data-slot="saveprefsbutton"') || str_contains($html, BuilderSubscriber::saveprefsRegex)) {
                        // set custom tag to inject end form
                        // update show pref center slots by looking for their presence in the html
                        $params     = array_merge(
                            $viewParameters,
                            [
                                'form'                         => $formView,
                                'startform'                    => $this->renderView('@MauticCore/Default/form.html.twig', ['form' => $formView]),
                                'custom_tag'                   => '<a name="end-'.$formView->vars['id'].'"></a>',
                                'showContactFrequency'         => str_contains($html, 'data-slot="channelfrequency"') || str_contains($html, BuilderSubscriber::channelfrequency),
                                'showContactSegments'          => str_contains($html, 'data-slot="segmentlist"') || str_contains($html, BuilderSubscriber::segmentListRegex),
                                'showContactCategories'        => str_contains($html, 'data-slot="categorylist"') || str_contains($html, BuilderSubscriber::categoryListRegex),
                                'showContactPreferredChannels' => str_contains($html, 'data-slot="preferredchannel"') || str_contains($html, BuilderSubscriber::preferredchannel),
                            ]
                        );
                        // Replace tokens in preference center page
                        $event = new PageDisplayEvent($html, $prefCenter, $params);
                        $this->dispatcher
                            ->dispatch($event, PageEvents::PAGE_ON_DISPLAY);
                        $html = $event->getContent();
                        if (!$session->has($successSessionName)) {
                            $successMessageDataSlots       = [
                                'data-slot="successmessage"',
                                'class="pref-successmessage"',
                            ];
                            $successMessageDataSlotsHidden = [];
                            foreach ($successMessageDataSlots as $successMessageDataSlot) {
                                $successMessageDataSlotsHidden[] = $successMessageDataSlot.' style=display:none';
                            }
                            $html = str_replace(
                                $successMessageDataSlots,
                                $successMessageDataSlotsHidden,
                                $html
                            );
                        } else {
                            $session->remove($successSessionName);
                        }
                        $html = preg_replace(
                            '/'.BuilderSubscriber::identifierToken.'/',
                            $lead->getPrimaryIdentifier(),
                            $html
                        );
                    } else {
                        unset($html);
                    }
                }

                if (empty($html)) {
                    $html = $this->render(
                        '@MauticEmail/Lead/preference_options.html.twig',
                        array_merge(
                            $viewParameters,
                            [
                                'form'         => $formView,
                                'currentRoute' => $this->generateUrl(
                                    'mautic_contact_action',
                                    [
                                        'objectAction' => 'contactFrequency',
                                        'objectId'     => $lead->getId(),
                                    ]
                                ),
                            ]
                        )
                    )->getContent();
                }
                $message = $html;
            }
        } else {
            $message = $translator->trans('mautic.email.stat_record.not_found');
        }

        $config = $theme->getConfig();

        $viewParams = [
            'email'    => $email,
            'lead'     => $lead,
            'template' => $template,
            'message'  => $message,
        ];

        if (!empty($formContent)) {
            $viewParams['content'] = $formContent;
            if (in_array('form', $config['features'])) {
                $contentTemplate = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/form.html.twig');
            } else {
                $viewParams['content'] = '';
                $viewParams['message'] = $message.$formContent;
            }
        }

        return $this->render($contentTemplate, $viewParams);
    }

    /**
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function resubscribeAction(ContactTracker $contactTracker, $idHash): Response
    {
        // find the email
        $model = $this->getModel('email');
        \assert($model instanceof EmailModel);
        $stat = $model->getEmailStatus($idHash);

        if (!empty($stat)) {
            $email = $stat->getEmail();
            $lead  = $stat->getLead();

            if ($lead) {
                // Set the lead as current lead
                $contactTracker->setTrackedContact($lead);

                if (!$this->translator instanceof LocaleAwareInterface) {
                    throw new \LogicException(sprintf('$this->translator must be an instance of "%s"', LocaleAwareInterface::class));
                }

                // Set lead lang
                if ($lead->getPreferredLocale()) {
                    $this->translator->setLocale($lead->getPreferredLocale());
                }
            }

            $model->removeDoNotContact($stat->getEmailAddress());

            $message = $this->coreParametersHelper->get('resubscribe_message');
            if (!$message) {
                $message = $this->translator->trans(
                    'mautic.email.resubscribed.success',
                    [
                        '%unsubscribeUrl%' => '|URL|',
                        '%email%'          => '|EMAIL|',
                    ]
                );
            }
            $message = str_replace(
                [
                    '|URL|',
                    '|EMAIL|',
                ],
                [
                    $this->generateUrl('mautic_email_unsubscribe', ['idHash' => $idHash]),
                    $stat->getEmailAddress(),
                ],
                $message
            );
        } else {
            $email   = $lead   = false;
            $message = $this->translator->trans('mautic.email.stat_record.not_found');
        }

        $template = (!empty($email) && 'mautic_code_mode' !== $email->getTemplate()) ? $email->getTemplate() : $this->coreParametersHelper->get('theme');

        $theme = $this->factory->getTheme($template);

        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }

        // Ensure template still exists
        $theme = $this->factory->getTheme($template);
        if (empty($theme) || $theme->getTheme() !== $template) {
            $template = $this->coreParametersHelper->get('theme');
        }

        $analytics = $this->factory->getHelper('twig.analytics')->getCode();

        if (!empty($analytics)) {
            $this->factory->getHelper('template.assets')->addCustomDeclaration($analytics);
        }

        $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/message.html.twig');

        return $this->render(
            $logicalName,
            [
                'message'  => $message,
                'type'     => 'notice',
                'email'    => $email,
                'lead'     => $lead,
                'template' => $template,
            ]
        );
    }

    /**
     * Handles mailer transport webhook post.
     */
    public function mailerCallbackAction(Request $request): Response
    {
        $event = new TransportWebhookEvent($request);
        $this->dispatcher->dispatch($event, EmailEvents::ON_TRANSPORT_WEBHOOK);

        return $event->getResponse() ?? new Response('No email transport that could process this callback was found', Response::HTTP_NOT_FOUND);
    }

    /**
     * Preview email.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction(AnalyticsHelper $analyticsHelper, $objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model       = $this->getModel('email');
        $emailEntity = $model->getEntity($objectId);

        if (null === $emailEntity) {
            return $this->notFound();
        }

        if (
            ($this->security->isAnonymous() && (!$emailEntity->getIsPublished() || !$emailEntity->isPublicPreview()))
            || (!$this->security->isAnonymous()
                && !$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $emailEntity->getCreatedBy()
                ))
        ) {
            return $this->accessDenied();
        }

        // bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        $BCcontent = $emailEntity->getContent();
        $content   = $emailEntity->getCustomHtml();
        if (empty($content) && !empty($BCcontent)) {
            $template = $emailEntity->getTemplate();
            $slots    = $this->factory->getTheme($template)->getSlots('email');

            $assetsHelper = $this->factory->getHelper('template.assets');

            $assetsHelper->addCustomDeclaration('<meta name="robots" content="noindex">');

            $this->processSlots($slots, $emailEntity);

            $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

            $response = $this->render(
                $logicalName,
                [
                    'inBrowser' => true,
                    'slots'     => $slots,
                    'content'   => $emailEntity->getContent(),
                    'email'     => $emailEntity,
                    'lead'      => null,
                    'template'  => $template,
                ]
            );

            // replace tokens
            $content = $response->getContent();
        }

        // Convert emojis
        $content = EmojiHelper::toEmoji($content, 'short');

        // Override tracking_pixel
        $tokens = ['{tracking_pixel}' => ''];

        // Prepare a fake lead
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->getModel('lead.field');
        $fields     = $fieldModel->getFieldList(false, false);
        array_walk(
            $fields,
            function (&$field): void {
                $field = "[$field]";
            }
        );
        $fields['id'] = 0;

        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $content,
                'email'        => $emailEntity,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $fields,
            ]
        );
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_DISPLAY);

        $content = $event->getContent(true);

        if ($this->security->isAnonymous()) {
            $content = $analyticsHelper->addCode($content);
        }

        return new Response($content);
    }

    /**
     * @param Email $entity
     */
    public function processSlots($slots, $entity): void
    {
        /** @var \Mautic\CoreBundle\Twig\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->factory->getHelper('template.slots');

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            $value = $content[$slot] ?? '';
            $slotsHelper->set($slot, $value);
        }
    }

    /**
     * @throws \Exception
     */
    private function doTracking(Request $request, IntegrationHelper $integrationHelper, MailHelper $mailer, LoggerInterface $mauticLogger, $integration): void
    {
        $logger = $mauticLogger;

        // if additional data were sent with the tracking pixel
        $query_string = $request->server->get('QUERY_STRING');
        if (!$query_string) {
            $logger->log('error', $integration.': query string is not available');

            return;
        }

        if (str_starts_with($query_string, 'r=')) {
            $query_string = substr($query_string, strpos($query_string, '?') + 1);
        } // remove route variable

        parse_str($query_string, $query);

        // URL attr 'd' is encoded so let's decode it first.
        if (!isset($query['d'], $query['sig'])) {
            $logger->log('error', $integration.': query variables are not found');

            return;
        }

        // get secret from plugin settings
        $myIntegration = $integrationHelper->getIntegrationObject($integration);

        if (!$myIntegration) {
            $logger->log('error', $integration.': integration not found');

            return;
        }
        $keys = $myIntegration->getDecryptedApiKeys();

        // generate signature
        $salt = $keys['secret'];
        if (!str_contains($salt, '$1$')) {
            $salt = '$1$'.$salt;
        } // add MD5 prefix
        $cr    = crypt(urlencode($query['d']), $salt);
        $mySig = hash('crc32b', $cr); // this hash type is used in c#

        // compare signatures
        if (hash_equals($mySig, $query['sig'])) {
            // decode and parse query variables
            $b64 = base64_decode($query['d']);
            $gz  = gzdecode($b64);
            parse_str($gz, $query);
        } else {
            // signatures don't match: stop
            $logger->log('error', $integration.': signatures don\'t match');

            unset($query);
        }

        if (empty($query) || !isset($query['email'], $query['subject'], $query['body'])) {
            $logger->log('error', $integration.': query variables are empty');

            return;
        }

        if (MAUTIC_ENV === 'dev') {
            $logger->log('error', $integration.': '.json_encode($query, JSON_PRETTY_PRINT));
        }

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        // email is a semicolon delimited list of emails
        $emails    = explode(';', $query['email']);
        $leadModel = $this->getModel('lead');
        \assert($leadModel instanceof LeadModel);
        $repo = $leadModel->getRepository();

        foreach ($emails as $email) {
            $lead = $repo->getLeadByEmail($email);
            if (null === $lead) {
                $lead = $this->createLead($email, $repo);
                if (null === $lead) {
                    continue;
                }
            }

            $idHash = hash('crc32', $email.$query['body']);
            $idHash = substr($idHash.$idHash, 0, 13); // 13 bytes length

            $stat = $model->getEmailStatus($idHash);

            // stat doesn't exist, create one
            if (null === $stat) {
                $lead['email'] = $email; // needed for stat
                $stat          = $this->addStat($mailer, $lead, $email, $query, $idHash);
            }

            $stat->setSource('email.client');

            if ($stat || 'Outlook' !== $integration) { // Outlook requests the tracking gif on send
                $model->hitEmail($idHash, $request); // add email event
            }
        }
    }

    /**
     * @return Response
     */
    public function pluginTrackingGifAction(Request $request, IntegrationHelper $integrationHelper, MailHelper $mailer, LoggerInterface $mauticLogger, $integration)
    {
        $this->doTracking($request, $integrationHelper, $mailer, $mauticLogger, $integration);

        return TrackingPixelHelper::getResponse($request); // send gif
    }

    private function addStat(MailHelper $mailer, $lead, $email, $query, $idHash): ?Stat
    {
        if (null !== $lead) {
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

            return $mailer->createEmailStat();
        }

        return null;
    }

    private function createLead($email, $repo): ?Lead
    {
        $model = $this->getModel('lead.lead');
        \assert($model instanceof LeadModel);
        $lead  = $model->getEntity();
        // set custom field values
        $data = ['email' => $email];
        $model->setFieldValues($lead, $data, true);
        // create lead
        $model->saveEntity($lead);

        // return entity
        return $repo->getLeadByEmail($email);
    }

    public function getUnsubscribeMessage($idHash, $model, $stat, $translator): string
    {
        $model->setDoNotContact($stat, $translator->trans('mautic.email.dnc.unsubscribed'), DoNotContact::UNSUBSCRIBED);

        $message = $this->coreParametersHelper->get('unsubscribe_message');
        if (!$message) {
            $message = $translator->trans(
                'mautic.email.unsubscribed.success',
                [
                    '%resubscribeUrl%' => '|URL|',
                    '%email%'          => '|EMAIL|',
                ]
            );
        }

        return str_replace(
            [
                '|URL|',
                '|EMAIL|',
            ],
            [
                $this->generateUrl('mautic_email_resubscribe', ['idHash' => $idHash]),
                $stat->getEmailAddress(),
            ],
            $message
        );
    }
}
