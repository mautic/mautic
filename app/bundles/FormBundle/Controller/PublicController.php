<?php

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Entity\FieldRepository;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class PublicController extends CommonFormController
{
    private CompanyRepository $companyRepository;

    private FieldRepository $fieldRepository;

    private SubmissionModel $submissionModel;

    private FormModel $formModel;

    #[Required]
    public function autowirePublicController(
        FormModel $formModel,
        SubmissionModel $submissionModel,
        FieldRepository $fieldRepository,
        CompanyRepository $companyRepository,
    ): void {
        $this->formModel = $formModel;
        $this->submissionModel = $submissionModel;
        $this->fieldRepository = $fieldRepository;
        $this->companyRepository = $companyRepository;
    }

    private array $tokens = [];

    public function submitAction(
        Request $request,
        DateHelper $dateTemplateHelper,
        PageTokenHelper $pageTokenHelper,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ): Response {
        if ('POST' !== $request->getMethod()) {
            $this->throwAccessDenied();
        }

        $submissionResult = $this->processSubmittedForm($request, $dateTemplateHelper, $notificationModel, $userRepository);

        if ($submissionResult['response'] instanceof Response) {
            return $submissionResult['response'];
        }

        if ($submissionResult['submissionEvent'] instanceof SubmissionEvent && !empty($submissionResult['postActionProperty'])) {
            // Replace post action property with tokens to support custom redirects, etc
            $submissionResult['postActionProperty'] = $this->replacePostSubmitTokens($submissionResult['postActionProperty'], $submissionResult['submissionEvent'], $pageTokenHelper);
        }

        return ($this->isMessengerMode($request) || $this->isAjax($request))
            ? $this->buildMessengerResponse($request, $submissionResult)
            : $this->buildStandardResponse($request, $submissionResult);
    }

    /**
     * @return array<string, mixed>
     */
    private function getSubmittedPost(Request $request): array
    {
        $post = $request->request->all()['mauticform'] ?? [];

        return is_array($post) ? $post : [];
    }

    private function isAjax(Request $request): bool
    {
        return (bool) $request->query->get('ajax');
    }

    private function isMessengerMode(Request $request): bool
    {
        $post = $this->getSubmittedPost($request);
        $messenger = $post['messenger'] ?? null;

        // This is a transport hint set by mautic-form JS to request messenger-style responses.
        // It must not be used for business/security decisions.
        return in_array($messenger, [1, '1', true, 'true'], true);
    }

    /**
     * Returns a sanitized return URL only if it is relative, same-origin as site_url,
     * or matches a domain listed in the CORS valid_domains configuration.
     * External/untrusted URLs are rejected and null is returned.
     */
    private function getTrustedReturnUrl(Request $request): ?string
    {
        $post   = $this->getSubmittedPost($request);
        $return = $post['return'] ?? '';

        if ('' === $return) {
            $return = $request->server->get('HTTP_REFERER', '');
        }

        if ('' === $return) {
            return null;
        }

        $rawReturn = (string) $return;
        $return = InputHelper::url($rawReturn, false, null, null, ['mauticError', 'mauticMessage'], true);

        // Reject URLs containing backslashes or @ signs that could be misinterpreted
        // by browsers differently than PHP's parse_url.
        if (str_contains($return, '\\') || (str_contains($return, '@') && !str_starts_with($return, '/'))) {
            return null;
        }

        // Allow relative URLs (starting with / but not //)
        if (str_starts_with($return, '/') && !str_starts_with($return, '//')) {
            return $return;
        }

        $returnHost = parse_url($return, PHP_URL_HOST);
        if (!$returnHost) {
            return null;
        }

        // Allow same-origin URLs (comparing scheme, host, and port)
        $siteUrl = (string) $this->coreParametersHelper->get('site_url');
        if ('' !== $siteUrl) {
            $siteScheme = parse_url($siteUrl, PHP_URL_SCHEME);
            $siteHost   = parse_url($siteUrl, PHP_URL_HOST);
            $sitePort   = parse_url($siteUrl, PHP_URL_PORT);

            $returnScheme = parse_url($return, PHP_URL_SCHEME);
            $returnPort   = parse_url($return, PHP_URL_PORT);

            // Only proceed with same-origin check if both URLs have schemes
            if ($siteScheme && $returnScheme) {
                // Normalize ports: null means default port for the scheme
                $sitePort ??= 'https' === $siteScheme ? 443 : 80;
                $returnPort ??= 'https' === $returnScheme ? 443 : 80;

                if ($siteHost && strtolower($siteScheme) === strtolower($returnScheme)
                    && strtolower($siteHost) === strtolower($returnHost)
                    && $sitePort === $returnPort) {
                    return $return;
                }
            }
        }

        // Allow URLs matching CORS valid domains configuration
        $validDomains = (array) $this->coreParametersHelper->get('cors_valid_domains');
        $returnOrigin = parse_url($return, PHP_URL_SCHEME).'://'.$returnHost;

        foreach ($validDomains as $validDomain) {
            if (fnmatch($validDomain, $returnOrigin, FNM_CASEFOLD)) {
                return $return;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function processSubmittedForm(
        Request $request,
        DateHelper $dateTemplateHelper,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ): array {
        $post = $this->getSubmittedPost($request);
        $result = [
            'callbackResponses'  => [],
            'error'              => null,
            'form'               => null,
            'postAction'         => null,
            'postActionProperty' => null,
            'response'           => null,
            'submissionEvent'    => null,
        ];

        if (!isset($post['formId'])) {
            $result['error'] = $this->translator->trans('mautic.form.submit.error.unavailable', [], 'flashes');
        } else {
            $form      = $this->formModel->getEntity($post['formId']);

            if (null === $form) {
                $result['error'] = $this->translator->trans('mautic.form.submit.error.unavailable', [], 'flashes');
            } else {
                $result['form']               = $form;
                $result['postAction']         = $form->getPostAction();
                $result['postActionProperty'] = $form->getPostActionProperty();
                $result['error']              = $this->getFormAvailabilityError($form, $dateTemplateHelper);

                if (null === $result['error']) {
                    $result = array_merge(
                        $result,
                        $this->handlePublishedForm($request, $form, $notificationModel, $userRepository)
                    );
                }
            }
        }

        return $result;
    }

    private function getFormAvailabilityError(Form $form, DateHelper $dateTemplateHelper): ?string
    {
        $status = $form->getPublishStatus();

        if ('pending' === $status) {
            $publishUp = $form->getPublishUp();

            return $this->translator->trans(
                'mautic.form.submit.error.pending',
                ['%date%' => $dateTemplateHelper->toFull($publishUp instanceof \DateTime ? $publishUp : $publishUp->format('Y-m-d H:i:s'))],
                'flashes'
            );
        }

        if ('expired' === $status) {
            $publishDown = $form->getPublishDown();

            return $this->translator->trans(
                'mautic.form.submit.error.expired',
                ['%date%' => $dateTemplateHelper->toFull($publishDown instanceof \DateTime ? $publishDown : $publishDown->format('Y-m-d H:i:s'))],
                'flashes'
            );
        }

        return ('published' !== $status)
            ? $this->translator->trans('mautic.form.submit.error.unavailable', [], 'flashes')
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function handlePublishedForm(
        Request $request,
        Form $form,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ): array {
        $this->doctrine->getManager()->refresh($form);

        if ($form->isSubmissionLimitReached()) {
            $this->notifySubmissionLimitReached($form, $notificationModel, $userRepository);

            return [
                'error' => $form->getSubmissionLimitMessage() ?? $this->translator->trans('mautic.form.submission.limit_reached'),
            ];
        }

        $post   = $this->getSubmittedPost($request);
        $server = $request->server->all();

        // Validate and replace the user-controlled 'return' field with a trusted URL.
        // This preserves the submission referrer metadata and repost actions, but prevents
        // untrusted URLs from being stored or used for redirects.
        $trustedReturn = $this->getTrustedReturnUrl($request);
        if (null !== $trustedReturn) {
            $post['return'] = $trustedReturn;
        } else {
            // If the provided return URL is untrusted, remove it to prevent
            // submission redirection to user-controlled URLs.
            unset($post['return']);
        }

        $result = $this->submissionModel->saveSubmission($post, $server, $form, $request, true);

        return $this->handleSubmissionResult($request, $result);
    }

    private function notifySubmissionLimitReached(Form $form, NotificationModel $notificationModel, UserRepository $userRepository): void
    {
        $ownerId = $form->getCreatedBy();
        if (!$ownerId) {
            return;
        }

        $user = $userRepository->find($ownerId);
        if (!$user) {
            return;
        }

        $notificationModel->addNotification(
            $this->translator->trans('mautic.form.submission.limit_reached.notification', ['%form%' => $form->getName()]),
            'warning',
            false,
            $form->getName(),
            null,
            null,
            $user
        );
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function handleSubmissionResult(Request $request, array $result): array
    {
        $messengerMode = $this->isMessengerMode($request);
        $isAjax        = $this->isAjax($request);

        if (!empty($result['errors'])) {
            return [
                'error' => $this->formatSubmissionErrors(
                    $result['errors'],
                    $messengerMode,
                    $isAjax
                ),
            ];
        }

        if (!empty($result['callback'])) {
            /** @var SubmissionEvent $submissionEvent */
            $submissionEvent = $result['callback'];
            $callbackResult  = $this->dispatchPostSubmitCallbacks(
                $submissionEvent,
                $messengerMode,
                $isAjax
            );

            return array_merge(['submissionEvent' => $submissionEvent], $callbackResult);
        }

        return isset($result['submission'])
            ? ['submissionEvent' => $result['submission']]
            : [];
    }

    /**
     * @return mixed
     */
    private function formatSubmissionErrors(mixed $errors, bool $messengerMode, bool $isAjax)
    {
        if ($messengerMode || $isAjax) {
            return $errors;
        }

        return is_array($errors)
            ? $this->translator->trans('mautic.form.submission.errors').'<br /><ol><li>'.implode('</li><li>', $errors).'</li></ol>'
            : (string) $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchPostSubmitCallbacks(SubmissionEvent $submissionEvent, bool $messengerMode, bool $isAjax): array
    {
        $callbackResponses  = $submissionEvent->getPostSubmitCallbackResponse();
        $callbacksRequested = $submissionEvent->getPostSubmitCallback();

        foreach ($callbacksRequested as $key => $callbackRequested) {
            $callbackRequested['messengerMode'] = $messengerMode;
            $callbackRequested['ajaxMode']      = $isAjax;

            if (isset($callbackRequested['eventName'])) {
                $submissionEvent->setPostSubmitCallback($key, $callbackRequested);
                $submissionEvent->setContext($key);

                $this->dispatcher->dispatch($submissionEvent, $callbackRequested['eventName']);
            }

            if ($submissionEvent->isPropagationStopped() && $submissionEvent->hasPostSubmitResponse()) {
                if (!$messengerMode) {
                    return [
                        'callbackResponses' => $callbackResponses,
                        'response'          => $submissionEvent->getPostSubmitResponse(),
                    ];
                }

                $callbackResponses[$key] = $submissionEvent->getPostSubmitResponse();
            }
        }

        return ['callbackResponses' => $callbackResponses];
    }

    /**
     * @param array<string, mixed> $submissionResult
     */
    private function buildMessengerResponse(Request $request, array $submissionResult): Response
    {
        $post = $this->getSubmittedPost($request);
        $data  = ['success' => 1];
        $error = $submissionResult['error'];

        if (!empty($error)) {
            if (is_array($error)) {
                $data['validationErrors'] = $error;
            } else {
                $data['errorMessage'] = $error;
            }
            $data['success'] = 0;
        } else {
            $data = $this->addMessengerSuccessData($data, $submissionResult);
        }

        if (isset($post['formName'])) {
            $data['formName'] = $post['formName'];
        }

        if ($this->isAjax($request)) {
            // Post via ajax so return a json response
            return new JsonResponse($data);
        }

        return $this->render('@MauticForm/messenger.html.twig', ['response' => json_encode($data)]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $submissionResult
     *
     * @return array<string, mixed>
     */
    private function addMessengerSuccessData(array $data, array $submissionResult): array
    {
        $submissionEvent = $submissionResult['submissionEvent'];
        if ($submissionEvent instanceof SubmissionEvent) {
            $data['results'] = $submissionEvent->getResults();
        }

        switch ($submissionResult['postAction']) {
            case 'redirect':
                $data['redirect'] = $submissionResult['postActionProperty'];
                break;
            case 'hideform':
                $data['hideform'] = true;
                // no break
            default:
                if (!empty($submissionResult['postActionProperty'])) {
                    $data['successMessage'] = [$submissionResult['postActionProperty']];
                }
                break;
        }

        $callbackResponses = $submissionResult['callbackResponses'];
        $data              = $this->addCallbackResponseData($data, is_array($callbackResponses) ? $callbackResponses : []);

        if (isset($data['successMessage'])) {
            $data['successMessage'] = implode('<br /><br />', $data['successMessage']);
        }

        return $data;
    }

    /**
     * @param array<string, mixed>     $data
     * @param array<int|string, mixed> $callbackResponses
     *
     * @return array<string, mixed>
     */
    private function addCallbackResponseData(array $data, array $callbackResponses): array
    {
        foreach ($callbackResponses as $response) {
            // Convert the responses to something useful for a JS response.
            if ($response instanceof RedirectResponse && !isset($data['redirect'])) {
                $data['redirect'] = $response->getTargetUrl();
            } elseif ($response instanceof Response) {
                $data['successMessage'] ??= [];
                $data['successMessage'][] = $response->getContent();
            } elseif (is_array($response)) {
                $data = array_merge($data, $response);
            } elseif (is_string($response)) {
                $data['successMessage'] ??= [];
                $data['successMessage'][] = $response;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $submissionResult
     */
    private function buildStandardResponse(Request $request, array $submissionResult): Response
    {
        $response = $this->getStandardRedirectResponse($request, $submissionResult);

        if (null === $response) {
            $msg     = $submissionResult['postActionProperty'];
            $msgType = 'notice';

            if (!empty($submissionResult['error'])) {
                $msg     = $submissionResult['error'];
                $msgType = 'error';
            } elseif ('return' === $submissionResult['postAction']) {
                $msg = $this->translator->trans('mautic.form.submission.thankyou');
            }

            $session = $request->getSession();
            $session->set(
                'mautic.emailbundle.message',
                [
                    'message' => $msg,
                    'type'    => $msgType,
                ]
            );

            $response = $this->redirectToRoute('mautic_form_postmessage');
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $submissionResult
     */
    private function getStandardRedirectResponse(Request $request, array $submissionResult): ?Response
    {
        $error = $submissionResult['error'];

        if ($error) {
            $return = $this->getTrustedReturnUrl($request);
            if ($return) {
                $form  = $submissionResult['form'];
                $hash  = ($form instanceof Form) ? '#'.strtolower($form->getAlias()) : '';
                $query = !str_contains($return, '?') ? '?' : '&';

                return $this->redirect($return.$query.'mauticError='.rawurlencode((string) $error).$hash);
            }
        } elseif ('redirect' === $submissionResult['postAction']) {
            return $this->redirect((string) $submissionResult['postActionProperty']);
        } elseif ('return' === $submissionResult['postAction']) {
            $return = $this->getTrustedReturnUrl($request);
            if ($return) {
                if (!empty($submissionResult['postActionProperty'])) {
                    $query  = !str_contains($return, '?') ? '?' : '&';
                    $return .= $query.'mauticMessage='.rawurlencode((string) $submissionResult['postActionProperty']);
                }

                return $this->redirect($return);
            }
        }

        return null;
    }

    /**
     * Displays a message.
     */
    public function messageAction(Request $request, AnalyticsHelper $analyticsHelper, AssetsHelper $assetsHelper, ThemeHelper $themeHelper): Response
    {
        $session = $request->getSession();
        $message = $session->get('mautic.emailbundle.message', []);

        $msg     = (!empty($message['message'])) ? $message['message'] : '';
        $msgType = (!empty($message['type'])) ? $message['type'] : 'notice';

        $analytics = $analyticsHelper->getCode();

        if (!empty($analytics)) {
            $assetsHelper->addCustomDeclaration($analytics);
        }

        $logicalName = $themeHelper->checkForTwigTemplate('@themes/'.$this->coreParametersHelper->get('theme').'/html/message.html.twig');

        return new Response($themeHelper->renderThemeTemplate($logicalName, [
            'message'  => $msg,
            'type'     => $msgType,
            'template' => $this->coreParametersHelper->get('theme'),
        ]));
    }

    /**
     * Gives a preview of the form.
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function previewAction(Request $request, AnalyticsHelper $analyticsHelper, AssetsHelper $assetsHelper, ThemeHelper $themeHelper, int $id = 0): Response
    {
        $objectId          = (empty($id)) ? (int) $request->get('id') : $id;
        $css               = InputHelper::string((string) $request->get('css'));
        $form              = $this->formModel->getEntity($objectId);
        $customStylesheets = (!empty($css)) ? explode(',', $css) : [];
        $template          = null;

        if (null === $form || !$form->isPublished()) {
            return $this->notFound();
        }
        $html = $this->formModel->getContent($form);

        $this->formModel->populateValuesWithGetParameters($form, $html);

        $viewParams = [
            'content'     => $html,
            'stylesheets' => $customStylesheets,
            'name'        => $form->getName(),
            'metaRobots'  => '<meta name="robots" content="index">',
        ];

        if ($form->getNoIndex()) {
            $viewParams['metaRobots'] = '<meta name="robots" content="noindex">';
        }

        // Use form specific template or system-wide default theme
        $template = $form->getTemplate() ?? $this->coreParametersHelper->get('theme');
        if (!empty($template)) {
            $theme = $themeHelper->getTheme($template);
            if ($theme->getTheme() != $template) {
                $config = $theme->getConfig();
                if (in_array('form', $config['features'])) {
                    $template = $theme->getTheme();
                } else {
                    $template = null;
                }
            }
        }

        $viewParams['template'] = $template;

        if (!empty($template)) {
            $logicalName  = $themeHelper->checkForTwigTemplate('@themes/'.$template.'/html/form.html.twig');
            $analytics    = $analyticsHelper->getCode();

            foreach ($customStylesheets as $css) {
                $assetsHelper->addStylesheet($css);
            }

            if (!empty($analytics)) {
                $assetsHelper->addCustomDeclaration($analytics);
            }
            if ($form->getNoIndex()) {
                $assetsHelper->addCustomDeclaration('<meta name="robots" content="noindex">');
            }

            return new Response($themeHelper->renderThemeTemplate($logicalName, $viewParams));
        }

        return $this->render('@MauticForm/form.html.twig', $viewParams);
    }

    /**
     * Generates JS file for automatic form generation.
     */
    public function generateAction(Request $request, FormModel $model): Response
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $formId = (int) $request->get('id');
        $form   = $this->formModel->getEntity($formId);
        $js     = '';

        if (null !== $form) {
            $status = $form->getPublishStatus();
            if ('published' === $status) {
                $js = $this->formModel->getAutomaticJavascript($form);
            }
        }

        $response = new Response();
        $response->setContent($js);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    public function embedAction(Request $request): Response
    {
        $formId = (int) $request->get('id');
        $form  = $this->formModel->getEntity($formId);

        if (null !== $form) {
            $status = $form->getPublishStatus();
            if ('published' === $status) {
                if ($request->get('video')) {
                    return $this->render(
                        '@MauticForm/Public/videoembed.html.twig',
                        ['form' => $form, 'fieldSettings' => $this->formModel->getCustomComponents()['fields']]
                    );
                }

                $content = $this->formModel->getContent($form, false, true);

                return new Response($content);
            }
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * @return string|string[]
     */
    private function replacePostSubmitTokens($string, SubmissionEvent $submissionEvent, PageTokenHelper $pageTokenHelper): string|array
    {
        if (count($this->tokens)) {
            return $this->tokens;
        }

        if ($lead = $submissionEvent->getLead()) {
            $this->tokens = array_merge(
                $submissionEvent->getTokens(),
                TokenHelper::findLeadTokens(
                    $string,
                    $lead->getProfileFields()
                )
            );
        }

        $this->tokens = array_merge(
            $this->tokens,
            $pageTokenHelper->findPageTokens($string)
        );

        return str_replace(array_keys($this->tokens), array_values($this->tokens), $string);
    }

    public function lookupCompanyAction(Request $request, FieldModel $fieldModel, CompanyModel $companyModel): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true);
        $search     = InputHelper::clean($parameters['search'] ?? '');
        $formId     = (int) ($parameters['formId'] ?? 0);

        // Intentionally vague message as the JS takes care of this.
        // Make it hard to abuse this public endpoint.
        $vagueErrorMessage = ['error' => 'Invalid request param'];

        if (mb_strlen($search) < 3 || !$formId) {
            return new JsonResponse($vagueErrorMessage, JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$this->fieldRepository->fieldExistsByFormAndType($formId, 'companyLookup')) {
            return new JsonResponse($vagueErrorMessage, JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->companyRepository->getCompanyLookupData($search));
    }
}
