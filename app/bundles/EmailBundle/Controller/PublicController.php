<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\EmailBundle\Form\Type\ValidateEmailType;
use Mautic\EmailBundle\Helper\EmailAddressLinkMatcher;
use Mautic\EmailBundle\Helper\EmailConfig;
use Mautic\EmailBundle\Helper\EmailDefaultsHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Controller\FrequencyRuleTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\EventListener\BuilderSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PublicController extends CommonFormController
{
    use FrequencyRuleTrait;

    private LeadRepository $leadRepository;

    private EmailModel $emailModel;

    private LeadModel $leadModel;

    private ThemeHelperInterface $themeHelper;

    private MailHashHelper $mailHashHelper;

    private EmailAddressLinkMatcher $emailAddressLinkMatcher;

    #[Required]
    public function autowirePublicController(
        LeadModel $leadModel,
        EmailModel $emailModel,
        LeadRepository $leadRepository,
        ThemeHelperInterface $themeHelper,
        MailHashHelper $mailHashHelper,
        EmailAddressLinkMatcher $emailAddressLinkMatcher,
    ): void {
        $this->leadModel = $leadModel;
        $this->emailModel = $emailModel;
        $this->leadRepository = $leadRepository;
        $this->themeHelper = $themeHelper;
        $this->mailHashHelper = $mailHashHelper;
        $this->emailAddressLinkMatcher = $emailAddressLinkMatcher;
    }

    public function indexAction(Request $request, AnalyticsHelper $analyticsHelper, string $idHash): Response
    {
        $stat = $this->emailModel->getEmailStatus($idHash);

        if ($stat instanceof Stat) {
            if ($this->security->isAnonymous()) {
                $this->emailModel->hitEmail($stat, $request, true);
            }

            $tokens = $stat->getTokens();
            if (is_array($tokens)) {
                // Override tracking_pixel so as to not cause a double hit
                $tokens['{tracking_pixel}'] = MailHelper::getBlankPixel();
            }

            if ($copy = $stat->getStoredCopy()) {
                $subject = $copy->getSubject();
                $content = $copy->getBody();

                // Replace tokens
                if (is_array($tokens)) {
                    $content = str_ireplace(array_keys($tokens), $tokens, $content);
                    $subject = str_ireplace(array_keys($tokens), $tokens, $subject);
                }
            } else {
                $subject = '';
                $content = '';
            }

            $content = $analyticsHelper->addCode($content);

            // Add subject as title
            if (!empty($subject)) {
                if (str_contains($content, '<title></title>')) {
                    $content = str_replace('<title></title>', "<title>{$subject}</title>", $content);
                } elseif (!str_contains($content, '<title>')) {
                    $content = str_replace('<head>', "<head>\n<title>{$subject}</title>", $content);
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
        string $idHash,
    ): Response {
        try {
            $messageBus->dispatch(new EmailHitNotification($idHash, $request));
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage(), ['idHash' => $idHash]);

            $this->emailModel->hitEmail($idHash, $request);
        }

        return TrackingPixelHelper::getResponse($request);
    }

    public function validateEmailAction(Request $request, string $action, string $secretHash, string $idHash): Response
    {
        if (!in_array($action, ['unsubscribe', 'resubscribe'])) {
            return $this->notFound();
        }

        $stat      = $this->emailModel->getEmailStatus($idHash);
        $themeName = null;

        if ($stat && $email = $stat->getEmail()) {
            $themeName = $email->getTemplate();
            if ('mautic_code_mode' === $themeName) {
                $themeName = null; // Use system default
            }
        }

        if (!$themeName) {
            $themeName = $this->coreParametersHelper->get('theme');
        }

        $contentTemplate = $this->themeHelper->checkForTwigTemplate('@themes/'.$themeName.'/html/email-validation.html.twig');

        // Use the same theme that was found above.
        if (preg_match('#^@themes/([^/]+)/#', $contentTemplate, $matches)) {
            $themeName = $matches[1];
        }

        $formAction = $this->generateUrl('mautic_email_validate_email_form', ['action' => $action, 'secretHash' => $secretHash, 'idHash' => $idHash]);
        $form       = $this->formFactory->create(
            ValidateEmailType::class,
            null,
            [
                'action'             => $formAction,
                'secret_hash'        => $secretHash,
                'stat_email_address' => $stat ? $stat->getEmailAddress() : null,
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $validatedEmail = $stat ? $stat->getEmailAddress() : $form->get('emailAddress')->getData();

            return $this->redirectToRoute(
                "mautic_email_{$action}",
                ['secretHash' => $secretHash, 'idHash' => $idHash, 'urlEmail' => $validatedEmail]
            );
        }

        return new Response($this->themeHelper->renderThemeTemplate(
            $contentTemplate,
            [
                'form'     => $form->createView(),
                'template' => $themeName,
            ]
        ));
    }

    public function unsubscribeAction(Request $request, ContactTracker $contactTracker, EmailModel $model, FormModel $formModel, PageModel $pageModel, MailHashHelper $mailHash, EmailDefaultsHelper $emailDefaultsHelper, string $idHash, ?string $secretHash = null, ?string $urlEmail = null): Response
    {
        $stat                   = $model->getEmailStatus($idHash);
        $hasStat                = $stat instanceof Stat;
        $message                = '';
        $email                  = null;
        $lead                   = null;
        $template               = null;
        $session                = $request->getSession();
        $isOneClickUnsubscribe  = $request->isMethod(Request::METHOD_POST) && 'One-Click' === $request->get('List-Unsubscribe');
        $isUnsubscribeAll       = $request->get('unsubscribe_all');
        $showContactPreferences = $this->coreParametersHelper->get('show_contact_preferences');
        $isHeadRequest          = $request->isMethod(Request::METHOD_HEAD);

        if ($isOneClickUnsubscribe) {
            return $this->oneClickUnsubscribe($model, $stat);
        }

        if (!$urlEmail || !$secretHash) {
            if (!$hasStat) {
                $message = $this->translator->trans('mautic.email.stat_record.not_found');
            } else {
                return $this->redirectToRoute('mautic_email_validate_email_form', [
                    'action'     => 'unsubscribe',
                    'secretHash' => $mailHash->getEmailHash($stat->getEmailAddress()),
                    'idHash'     => $idHash,
                ]);
            }
        }

        $urlEmail = trim((string) $urlEmail);

        if (!$urlEmail) {
            $urlEmail = $stat ? $stat->getEmailAddress() : '';
        }

        if ($hasStat && $email = $stat->getEmail()) {
            $template = $email->getTemplate();
            if ('mautic_code_mode' === $template) {
                $template = null; // Use system default
            }

            /** @var \Mautic\FormBundle\Entity\Form $unsubscribeForm */
            $unsubscribeForm = $email->getUnsubscribeForm();
            if (null != $unsubscribeForm && $unsubscribeForm->isPublished()) {
                $formTemplate = $unsubscribeForm->getTemplate();
                $formContent  = '<div class="mautic-unsubscribeform">'.$formModel->getContent($unsubscribeForm).'</div>';
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

        $theme = $this->themeHelper->getTheme($template);
        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }
        $contentTemplate = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/message.html.twig');
        $isCorrectHash   = false;
        if ($secretHash && $urlEmail) {
            $isCorrectHash = $this->emailAddressLinkMatcher->matchesLink(
                $urlEmail,
                $secretHash,
                $hasStat ? $stat->getEmailAddress() : null
            );
        }
        if ($isCorrectHash) {
            $successSessionName = 'mautic.email.prefscenter.success';
            if ($hasStat && $lead = $stat->getLead()) {
                // Set the lead as current lead
                $contactTracker->setTrackedContact($lead);

                // Set lead lang
                if ($language = $lead->getPreferredLocale()) {
                    $this->translator->setLocale($language);
                }

                // Add contact ID to the session name in case more contacts
                // share the same session/device and the contact is known.
                $successSessionName .= ".{$lead->getId()}";
            } elseif (!$hasStat) {
                $contacts = $this->leadRepository->getContactsByEmail($urlEmail);
                $lead     = null;
                if (is_array($contacts) && count($contacts) > 0) {
                    $lead  = array_pop($contacts);
                } else {
                    $message = $this->translator->trans('mautic.email.stat_record.not_found');
                }
            }

            if (!$isHeadRequest && (!$showContactPreferences || $isUnsubscribeAll || !$lead)) {
                if ($hasStat) {
                    $message = $this->getUnsubscribeMessage($idHash, $model, $stat, $this->translator);
                } elseif ($lead && $lead instanceof Lead) {
                    $message = $this->getUnsubscribeMessageLead($idHash, $model, $lead, $this->translator, $urlEmail);
                }
            } elseif ($lead) {
                $params = ['idHash' => $idHash, 'urlEmail' => $urlEmail];

                if ($urlEmail) {
                    $params['secretHash'] = $mailHash->getEmailHash($urlEmail);
                }

                $action          = $this->generateUrl('mautic_email_unsubscribe', $params);
                $viewParameters  = $this->getViewParams($lead, $idHash, $params);
                $form            = $this->getFrequencyRuleForm($lead, $viewParameters, $data, true, $action, true);

                if ($session->get($successSessionName)) {
                    $viewParameters['successMessage'] = $this->translator->trans('mautic.email.preferences_center_success_message.text');
                }

                if (true === $form) {
                    $session->set($successSessionName, 1);

                    return $this->postActionRedirect(
                        [
                            'returnUrl'       => $action,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $contentTemplate,
                        ]
                    );
                }
                // success message should not persist on page refresh
                $session->set($successSessionName, 0);

                $formView = $form->createView();

                $html = $this->getPreferenceCenterHtml(
                    $request,
                    $lead,
                    $email,
                    $formView,
                    $viewParameters,
                    $language ?? null,
                    $successSessionName,
                    $emailDefaultsHelper,
                    $pageModel
                );

                if (empty($html)) {
                    $html = $this->getHtml($formView, $lead, $viewParameters);
                }
                $message = $html;
            }
        } else {
            $message = $this->translator->trans('mautic.email.stat_record.not_found');
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
                $contentTemplate = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/form.html.twig');
            } else {
                $viewParams['content'] = '';
                $viewParams['message'] = $message.$formContent;
            }
        }

        return new Response($this->themeHelper->renderThemeTemplate($contentTemplate, $viewParams));
    }

    /**
     * @param array<mixed> $viewParameters
     */
    private function getPreferenceCenterHtml(Request $request, Lead $lead, ?Email $email, FormView $formView, array $viewParameters, ?string $language, string $successSessionName, EmailDefaultsHelper $emailDefaultsHelper, PageModel $pageModel): ?string
    {
        $prefCenter = $email instanceof Email ? $emailDefaultsHelper->resolvePreferenceCenter($email) : null;
        if (!$prefCenter instanceof Page) {
            return null;
        }

        // Set the page language if there is no lead preferred locale.
        if (empty($language) && $language = $prefCenter->getLanguage()) {
            $this->translator->setLocale($language);
        }

        $html = $prefCenter->getCustomHtml();
        if (!str_contains($html, BuilderSubscriber::saveprefsRegex)) {
            return null;
        }

        $showParameters  = $this->buildShowParametersBasedOnContent($html, $viewParameters);
        $eventParameters = array_merge(
            $viewParameters,
            $showParameters,
            [
                'form'       => $formView,
                'startform'  => $this->renderView('@MauticCore/Default/form.html.twig', ['form' => $formView]),
                'custom_tag' => '<a name="end-'.$formView->vars['id'].'"></a>',
            ]
        );

        $event = new PageDisplayEvent($html, $prefCenter, $eventParameters);
        $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);

        $request->getSession()->remove($successSessionName);
        $pageModel->hitPage($prefCenter, $request, 200, $lead);

        return preg_replace(
            '/'.BuilderSubscriber::identifierToken.'/',
            $lead->getPrimaryIdentifier(),
            $event->getContent()
        );
    }

    public function unsubscribeAllAction(Request $request, string $idHash, ?string $urlEmail = null, ?string $secretHash = null): Response
    {
        $request->attributes->set('unsubscribe_all', 1);

        return $this->forward(self::class.'::unsubscribeAction', [
            'request'    => $request,
            'idHash'     => $idHash,
            'urlEmail'   => $urlEmail,
            'secretHash' => $secretHash,
        ]);
    }

    /**
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function resubscribeAction(ContactTracker $contactTracker, EmailModel $model, MailHashHelper $mailHash, AssetsHelper $assetsHelper, AnalyticsHelper $analyticsHelper, string $idHash, ?string $urlEmail = null, ?string $secretHash = null): Response
    {
        $stat    = $model->getEmailStatus($idHash);
        $hasStat = $stat instanceof Stat;

        if ($hasStat) {
            if ($urlEmail && $secretHash) {
                // Coming from validate form — verify hash before performing action.
                $isCorrectHash = $mailHash->getEmailHash($urlEmail) === $secretHash;
                if ($isCorrectHash) {
                    $email = $lead = false;
                    $model->removeDoNotContact($urlEmail);
                    $message = $this->buildResubscribeSuccessMessage($urlEmail, $secretHash, $idHash);
                } else {
                    $email   = false;
                    $lead    = false;
                    $message = $this->translator->trans('mautic.email.stat_record.not_found');
                }
            } else {
                // Legacy link without email/hash — redirect to the validate form.
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

                return $this->redirectToRoute(
                    'mautic_email_validate_email_form',
                    [
                        'action'     => 'resubscribe',
                        'secretHash' => $mailHash->getEmailHash($stat->getEmailAddress()),
                        'idHash'     => $idHash,
                    ]
                );
            }
        } elseif (!$urlEmail || !$secretHash) {
            return $this->notFound();
        } else {
            $isCorrectHash = $mailHash->getEmailHash($urlEmail) === $secretHash;

            if ($isCorrectHash) {
                $email = $lead = false;
                $model->removeDoNotContact($urlEmail);
                $message = $this->buildResubscribeSuccessMessage($urlEmail, $secretHash, $idHash);
            } else {
                $email   = $lead   = false;
                $message = $this->translator->trans('mautic.email.stat_record.not_found');
            }
        }

        $template = $this->coreParametersHelper->get('theme');

        $theme = $this->themeHelper->getTheme($template);

        if ($theme->getTheme() != $template) {
            $template = $theme->getTheme();
        }

        // Ensure template still exists
        $theme = $this->themeHelper->getTheme($template);
        if (empty($theme) || $theme->getTheme() !== $template) {
            $template = $this->coreParametersHelper->get('theme');
        }

        $analytics = $analyticsHelper->getCode();

        if (!empty($analytics)) {
            $assetsHelper->addCustomDeclaration($analytics);
        }

        $logicalName = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/message.html.twig');

        return new Response($this->themeHelper->renderThemeTemplate(
            $logicalName,
            [
                'message'  => $message,
                'type'     => 'notice',
                'email'    => $email,
                'lead'     => $lead,
                'template' => $template,
            ]
        ));
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

    public function previewAction(
        AnalyticsHelper $analyticsHelper,
        AssetsHelper $assetsHelper,
        EmailConfig $emailConfig,
        EmailModel $model,
        Request $request,
        FakeContactHelper $fakeLeadHelper,
        string $objectId,
        ?string $objectType = null,
    ): Response {
        $contactId   = (int) $request->query->get('contactId');
        $emailEntity = $model->getEntity($objectId);

        if (null === $emailEntity) {
            return $this->notFound();
        }

        $publicPreview = $emailEntity->isPublicPreview();
        $draftEnabled  = $emailConfig->isDraftEnabled();
        if ('draft' === $objectType && $draftEnabled && $emailEntity->hasDraft()) {
            $publicPreview = $emailEntity->getDraft()->isPublicPreview();
        }

        if (
            ($this->security->isAnonymous() && !$publicPreview)
            || (!$this->security->isAnonymous()
                && !$this->security->hasEntityAccess(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    $emailEntity->getCreatedBy()
                ))
        ) {
            $this->throwAccessDenied();
        }

        // bogus ID
        if ($contactId && (
            !$this->security->isAdmin()
            && !$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother')
        )
        ) {
            // disallow displaying contact information
            $contactId = null;
        }

        // bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        $content = $emailEntity->getCustomHtml();

        if ('draft' === $objectType && $draftEnabled && $emailEntity->hasDraft()) {
            $content = $emailEntity->getDraftContent();
        }

        if (empty($content) && $emailEntity->getTemplate()) {
            $template = $emailEntity->getTemplate();

            $assetsHelper->addCustomDeclaration('<meta name="robots" content="noindex">');

            $logicalName = $this->themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/email.html.twig');

            $content = $this->themeHelper->renderThemeTemplate(
                $logicalName,
                [
                    'inBrowser' => true,
                    'content'   => $emailEntity->getContent(),
                    'email'     => $emailEntity,
                    'lead'      => null,
                    'template'  => $template,
                ]
            );
        }

        // Override tracking_pixel
        $tokens = ['{tracking_pixel}' => ''];

        // Prepare contact
        if ($contactId) {
            // We have one from request parameter
            $contact = $this->leadRepository->getLead($contactId);
            $contact = $model->enrichedContactWithCompanies($contact);
        } else {
            // Make fake contact.
            /** @var FakeContactHelper $fakeLeadHelper */
            $contact = $fakeLeadHelper->prepareFakeContactWithPrimaryCompany();
        }
        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $content,
                'email'        => $emailEntity,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $contact,
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

        // email is a semicolon delimited list of emails
        $emails    = explode(';', $query['email']);

        foreach ($emails as $email) {
            $lead = $this->leadRepository->getLeadByEmail($email);
            if (null === $lead) {
                $lead = $this->createLead($email);
                if (null === $lead) {
                    continue;
                }
            }

            $idHash = hash('crc32', $email.$query['body']);
            $idHash = substr($idHash.$idHash, 0, 13); // 13 bytes length

            $stat = $this->emailModel->getEmailStatus($idHash);

            // stat doesn't exist, create one
            if (null === $stat) {
                $lead['email'] = $email; // needed for stat
                $stat          = $this->addStat($mailer, $lead, $email, $query, $idHash);
            }

            $stat->setSource('email.client');

            if ($stat || 'Outlook' !== $integration) { // Outlook requests the tracking gif on send
                $this->emailModel->hitEmail($idHash, $request); // add email event
            }
        }
    }

    public function pluginTrackingGifAction(Request $request, IntegrationHelper $integrationHelper, MailHelper $mailer, LoggerInterface $mauticLogger, $integration): Response
    {
        $this->doTracking($request, $integrationHelper, $mailer, $mauticLogger, $integration);

        return TrackingPixelHelper::getResponse($request); // send gif
    }

    /**
     * @param array<string, mixed> $query
     */
    private function addStat(MailHelper $mailer, $lead, string $email, array $query, string $idHash): ?Stat
    {
        if (null !== $lead) {
            // To lead
            $mailer->addTo($email);

            // sanitize variables to prevent malicious content
            $from = filter_var($query['from'], FILTER_SANITIZE_EMAIL);
            $mailer->setFrom($from, '');

            // Set Content
            $body = htmlspecialchars(filter_var($query['body'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH));
            $mailer->setBody($body);
            $mailer->parsePlainText($body);

            // Set lead
            $mailer->setLead($lead);
            $mailer->setIdHash($idHash);

            $subject = htmlspecialchars(filter_var($query['subject'], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH));
            $mailer->setSubject($subject);

            return $mailer->createEmailStat();
        }

        return null;
    }

    private function createLead(string $email): ?array
    {
        $lead  = $this->leadModel->getEntity();
        // set custom field values
        $data = ['email' => $email];
        $this->leadModel->setFieldValues($lead, $data, true);
        // create lead
        $this->leadModel->saveEntity($lead);

        // return entity
        return $this->leadRepository->getLeadByEmail($email);
    }

    public function getUnsubscribeMessage(string $idHash, $model, $stat, TranslatorInterface $translator): string
    {
        $model->setDoNotContact($stat, $translator->trans('mautic.email.dnc.unsubscribed'), DoNotContact::UNSUBSCRIBED);

        return $this->getUnsubscribeText($translator, $stat->getEmailAddress(), $idHash);
    }

    public function getUnsubscribeMessageLead(string $idHash, EmailModel $model, Lead $lead, TranslatorInterface $translator, string $urlEmail): string
    {
        $model->setDoNotContactLead($lead, $translator->trans('mautic.email.dnc.unsubscribed'), DoNotContact::UNSUBSCRIBED);

        return $this->getUnsubscribeText($translator, $urlEmail, $idHash);
    }

    private function getUnsubscribeText(TranslatorInterface $translator, string $email, string $idHash): string
    {
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
                $this->generateUrl('mautic_email_validate_email_form', ['action' => 'resubscribe', 'secretHash' => $this->mailHashHelper->getEmailHash($email), 'idHash' => $idHash]),
                $email,
            ],
            $message
        );
    }

    private function buildResubscribeSuccessMessage(string $emailAddress, string $unsubscribeHash, string $idHash): string
    {
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

        return str_replace(
            [
                '|URL|',
                '|EMAIL|',
            ],
            [
                $this->generateUrl('mautic_email_validate_email_form', ['action' => 'unsubscribe', 'secretHash' => $unsubscribeHash, 'idHash' => $idHash]),
                $emailAddress,
            ],
            $message
        );
    }

    /**
     * The $viewParameters here have already been used to build the $form.
     * Fields that are set to show based on the app configuration are part
     * of the form. If the field is not configured to show, but a token exists
     * for that field in the content, then we need to keep the configuration
     * value instead of letting the content determine if it should show. This
     * is because of what was stated above - fields that are not configured to
     * to show are not part of the form. Attempting to render them will result
     * in an error.
     *
     * @param mixed[] $viewParameters
     *
     * @return mixed[]
     */
    private function buildShowParametersBasedOnContent(string $content, array $viewParameters): array
    {
        /*
         * Since we're going to be merging this with the $viewParameters, filter out `true` values. We do not
         * want to change a configured value from `false` to `true` because a value of `false` in the $viewParameters
         * means that the field is not configured to show and therefore is not part of the form. Attempting to
         * render that field just because a token for it exists will result in an error.
         */
        $showParamsBasedOnContent = array_filter([
            'showContactFrequency'         => str_contains($content, BuilderSubscriber::channelfrequency),
            'showContactSegments'          => str_contains($content, BuilderSubscriber::segmentListRegex),
            'showContactCategories'        => str_contains($content, BuilderSubscriber::categoryListRegex),
            'showContactPreferredChannels' => str_contains($content, BuilderSubscriber::preferredchannel),
        ], fn (bool $value): bool =>!$value);

        $showParamsBasedOnConfiguration = array_filter($viewParameters, fn ($key): bool => str_starts_with($key, 'show'), ARRAY_FILTER_USE_KEY);

        return array_merge($showParamsBasedOnConfiguration, $showParamsBasedOnContent);
    }

    private function oneClickUnsubscribe(EmailModel $model, ?Stat $stat): Response
    {
        if (!$stat) {
            $statsNotFount = $this->translator->trans('mautic.email.stat_record.not_found');

            return new Response($statsNotFount, Response::HTTP_NOT_FOUND);
        }

        // RFC 8058 One-Click unsubscribe
        $unsubscribeComment = $this->translator->trans('mautic.email.dnc.unsubscribed');
        $model->setDoNotContact($stat, $unsubscribeComment, DoNotContact::UNSUBSCRIBED);

        return new Response($this->translator->trans('mautic.lead.do.not.contact_unsubscribed'));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<mixed>
     */
    private function getViewParams(Lead $lead, string $idHash, array $params): array
    {
        return [
            'lead'                         => $lead,
            'idHash'                       => $idHash,
            'showContactFrequency'         => $this->coreParametersHelper->get('show_contact_frequency'),
            'showContactPauseDates'        => $this->coreParametersHelper->get('show_contact_pause_dates'),
            'showContactPreferredChannels' => $this->coreParametersHelper->get('show_contact_preferred_channels'),
            'showContactCategories'        => $this->coreParametersHelper->get('show_contact_categories'),
            'showContactSegments'          => $this->coreParametersHelper->get('show_contact_segments'),
            'dncUrl'                       => $this->generateUrl('mautic_email_unsubscribe_all', $params),
        ];
    }

    /**
     * @param array<mixed> $viewParameters
     */
    private function getHtml(FormView $formView, Lead $lead, array $viewParameters): string
    {
        return $this->render(
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
}
