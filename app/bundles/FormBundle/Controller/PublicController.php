<?php

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonFormController
{
    private array $tokens = [];

    /**
     * @return RedirectResponse|Response
     */
    public function submitAction(
        Request $request,
        DateHelper $dateTemplateHelper,
        PageTokenHelper $pageTokenHelper,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ) {
        if ('POST' !== $request->getMethod()) {
            return $this->accessDenied();
        }

        $context          = $this->createSubmitContext($request);
        $submissionResult = $this->processSubmittedForm($request, $context, $dateTemplateHelper, $notificationModel, $userRepository);

        if ($submissionResult['response'] instanceof Response) {
            return $submissionResult['response'];
        }

        if ($submissionResult['submissionEvent'] instanceof SubmissionEvent && !empty($submissionResult['postActionProperty'])) {
            // Replace post action property with tokens to support custom redirects, etc
            $submissionResult['postActionProperty'] = $this->replacePostSubmitTokens($submissionResult['postActionProperty'], $submissionResult['submissionEvent'], $pageTokenHelper);
        }

        return ($context['messengerMode'] || $context['isAjax'])
            ? $this->buildMessengerResponse($context, $submissionResult)
            : $this->buildStandardResponse($request, $context, $submissionResult);
    }

    /**
     * @return array<string, mixed>
     */
    private function createSubmitContext(Request $request): array
    {
        $server = $request->server->all();
        $post   = $request->request->all()['mauticform'] ?? [];
        $post   = is_array($post) ? $post : [];
        $return = $post['return'] ?? false;
        $query  = '?';

        if (empty($return)) {
            // try to get it from the HTTP_REFERER
            $return = $server['HTTP_REFERER'] ?? false;
        }

        if (!empty($return)) {
            // remove mauticError and mauticMessage from the referer so it doesn't get sent back
            $return = InputHelper::url((string) $return, false, null, null, ['mauticError', 'mauticMessage'], true);
            $query  = (!str_contains($return, '?')) ? '?' : '&';
        }

        return [
            'isAjax'        => (bool) $request->query->get('ajax'),
            'messengerMode' => !empty($post['messenger']),
            'post'          => $post,
            'query'         => $query,
            'return'        => $return,
            'server'        => $server,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function processSubmittedForm(
        Request $request,
        array $context,
        DateHelper $dateTemplateHelper,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ): array {
        $result = [
            'callbackResponses'  => [],
            'error'              => null,
            'form'               => null,
            'postAction'         => null,
            'postActionProperty' => null,
            'response'           => null,
            'submissionEvent'    => null,
        ];
        $post = $context['post'];
        \assert(is_array($post));

        if (!isset($post['formId'])) {
            $result['error'] = $this->translator->trans('mautic.form.submit.error.unavailable', [], 'flashes');
        } else {
            $formModel = $this->getModel('form.form');
            $form      = $formModel->getEntity($post['formId']);

            if (null === $form) {
                $result['error'] = $this->translator->trans('mautic.form.submit.error.unavailable', [], 'flashes');
            } else {
                \assert($form instanceof Form);

                $result['form']               = $form;
                $result['postAction']         = $form->getPostAction();
                $result['postActionProperty'] = $form->getPostActionProperty();
                $result['error']              = $this->getFormAvailabilityError($form, $dateTemplateHelper);

                if (null === $result['error']) {
                    $result = array_merge(
                        $result,
                        $this->handlePublishedForm($request, $context, $form, $notificationModel, $userRepository)
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
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function handlePublishedForm(
        Request $request,
        array $context,
        Form $form,
        NotificationModel $notificationModel,
        UserRepository $userRepository,
    ): array {
        $formSubmissionModel = $this->getModel('form.submission');
        \assert($formSubmissionModel instanceof SubmissionModel);

        $this->doctrine->getManager()->refresh($form);

        if ($form->isSubmissionLimitReached()) {
            $this->notifySubmissionLimitReached($form, $notificationModel, $userRepository);

            return [
                'error' => $form->getSubmissionLimitMessage() ?? $this->translator->trans('mautic.form.submission.limit_reached'),
            ];
        }

        $post   = $context['post'];
        $server = $context['server'];
        \assert(is_array($post));
        \assert(is_array($server));

        $result = $formSubmissionModel->saveSubmission($post, $server, $form, $request, true);

        return $this->handleSubmissionResult($result, $context);
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
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function handleSubmissionResult(array $result, array $context): array
    {
        if (!empty($result['errors'])) {
            return [
                'error' => $this->formatSubmissionErrors(
                    $result['errors'],
                    (bool) $context['messengerMode'],
                    (bool) $context['isAjax']
                ),
            ];
        }

        if (!empty($result['callback'])) {
            /** @var SubmissionEvent $submissionEvent */
            $submissionEvent = $result['callback'];
            $callbackResult  = $this->dispatchPostSubmitCallbacks(
                $submissionEvent,
                (bool) $context['messengerMode'],
                (bool) $context['isAjax']
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
     * @param array<string, mixed> $context
     * @param array<string, mixed> $submissionResult
     */
    private function buildMessengerResponse(array $context, array $submissionResult): Response
    {
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

        $post = $context['post'];
        \assert(is_array($post));

        if (isset($post['formName'])) {
            $data['formName'] = $post['formName'];
        }

        if ((bool) $context['isAjax']) {
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
     * @param array<string, mixed> $context
     * @param array<string, mixed> $submissionResult
     */
    private function buildStandardResponse(Request $request, array $context, array $submissionResult): Response
    {
        $response = $this->getStandardRedirectResponse($context, $submissionResult);

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
     * @param array<string, mixed> $context
     * @param array<string, mixed> $submissionResult
     */
    private function getStandardRedirectResponse(array $context, array $submissionResult): ?Response
    {
        $error    = $submissionResult['error'];
        $response = null;

        if (!empty($error) && $context['return']) {
            $form = $submissionResult['form'];
            $hash = ($form instanceof Form) ? '#'.strtolower($form->getAlias()) : '';

            $response = $this->redirect($context['return'].$context['query'].'mauticError='.rawurlencode((string) $error).$hash); // NOSONAR return URL is sanitized in createSubmitContext().
        } elseif ('redirect' === $submissionResult['postAction']) {
            $response = $this->redirect((string) $submissionResult['postActionProperty']);
        } elseif ('return' === $submissionResult['postAction'] && !empty($context['return'])) {
            $return = (string) $context['return'];
            if (!empty($submissionResult['postActionProperty'])) {
                $return .= $context['query'].'mauticMessage='.rawurlencode((string) $submissionResult['postActionProperty']);
            }

            $response = $this->redirect($return); // NOSONAR return URL is sanitized in createSubmitContext().
        }

        return $response;
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
     * @return Response
     *
     * @throws \Exception
     * @throws \Mautic\CoreBundle\Exception\FileNotFoundException
     */
    public function previewAction(Request $request, AnalyticsHelper $analyticsHelper, AssetsHelper $assetsHelper, ThemeHelper $themeHelper, int $id = 0)
    {
        $model = $this->getModel('form.form');
        \assert($model instanceof FormModel);
        $objectId          = (empty($id)) ? (int) $request->get('id') : $id;
        $css               = InputHelper::string((string) $request->get('css'));
        $form              = $model->getEntity($objectId);
        $customStylesheets = (!empty($css)) ? explode(',', $css) : [];
        $template          = null;

        if (null === $form || !$form->isPublished()) {
            return $this->notFound();
        }
        $html = $model->getContent($form);

        $model->populateValuesWithGetParameters($form, $html);

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
    public function generateAction(Request $request): Response
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $formId = (int) $request->get('id');

        $model = $this->getModel('form.form');
        \assert($model instanceof FormModel);
        $form  = $model->getEntity($formId);
        $js    = '';

        if (null !== $form) {
            $status = $form->getPublishStatus();
            if ('published' === $status) {
                $js = $model->getAutomaticJavascript($form);
            }
        }

        $response = new Response();
        $response->setContent($js);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @return Response
     */
    public function embedAction(Request $request)
    {
        $formId = (int) $request->get('id');
        /** @var FormModel $model */
        $model = $this->getModel('form');
        $form  = $model->getEntity($formId);

        if (null !== $form) {
            $status = $form->getPublishStatus();
            if ('published' === $status) {
                if ($request->get('video')) {
                    return $this->render(
                        '@MauticForm/Public/videoembed.html.twig',
                        ['form' => $form, 'fieldSettings' => $model->getCustomComponents()['fields']]
                    );
                }

                $content = $model->getContent($form, false, true);

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

        if (!$fieldModel->getRepository()->fieldExistsByFormAndType($formId, 'companyLookup')) {
            return new JsonResponse($vagueErrorMessage, JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($companyModel->getRepository()->getCompanyLookupData($search));
    }
}
