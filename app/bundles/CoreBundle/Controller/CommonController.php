<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Exporter\Handler;
use Exporter\Source\ArraySourceIterator;
use Exporter\Source\IteratorSourceIterator;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\XlsWriter;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DataExporterHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CommonController.
 */
class CommonController extends Controller implements MauticController
{
    use FormThemeTrait;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
    }

    /**
     * Check if a security level is granted.
     *
     * @param $level
     *
     * @return bool
     */
    protected function accessGranted($level)
    {
        return in_array($level, $this->getPermissions());
    }

    /**
     * Override this method in your controller
     * for easy access to the permissions.
     *
     * @return array
     */
    protected function getPermissions()
    {
        return [];
    }

    /**
     * Get a model instance from the service container.
     *
     * @param $modelNameKey
     *
     * @return AbstractCommonModel
     */
    protected function getModel($modelNameKey)
    {
        return $this->container->get('mautic.model.factory')->getModel($modelNameKey);
    }

    /**
     * Forwards the request to another controller and include the POST.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $request    An array of request parameters
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     *
     * @return Response A Response instance
     */
    public function forwardWithPost($controller, array $request = [], array $path = [], array $query = [])
    {
        $path['_controller'] = $controller;
        $subRequest          = $this->container->get('request_stack')->getCurrentRequest()->duplicate($query, $request, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Determines if ajax content should be returned or direct content (page refresh).
     *
     * @param array $args
     *
     * @return JsonResponse|Response
     */
    public function delegateView($args)
    {
        // Used for error handling
        defined('MAUTIC_DELEGATE_VIEW') || define('MAUTIC_DELEGATE_VIEW', 1);

        if (!is_array($args)) {
            $args = [
                'contentTemplate' => $args,
                'passthroughVars' => [
                    'mauticContent' => strtolower($this->request->get('bundle')),
                ],
            ];
        }

        if (!isset($args['viewParameters']['currentRoute']) && isset($args['passthroughVars']['route'])) {
            $args['viewParameters']['currentRoute'] = $args['passthroughVars']['route'];
        }

        if (!isset($args['viewParameters']['mauticContent'])) {
            if (isset($args['passthroughVars']['mauticContent'])) {
                $mauticContent = $args['passthroughVars']['mauticContent'];
            } else {
                $mauticContent = strtolower($this->request->get('bundle'));
            }
            $args['viewParameters']['mauticContent'] = $mauticContent;
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction($args);
        }

        $parameters = (isset($args['viewParameters'])) ? $args['viewParameters'] : [];
        $template   = $args['contentTemplate'];

        $code     = (isset($args['responseCode'])) ? $args['responseCode'] : 200;
        $response = new Response('', $code);

        return $this->render($template, $parameters, $response);
    }

    /**
     * Determines if a redirect response should be returned or a Json response directing the ajax call to force a page
     * refresh.
     *
     * @param $url
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delegateRedirect($url)
    {
        if ($this->request->isXmlHttpRequest()) {
            return new JsonResponse(['redirect' => $url]);
        } else {
            return $this->redirect($url);
        }
    }

    /**
     * Redirects URLs with trailing slashes in order to prevent 404s.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo   = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * Redirects /s and /s/ to /s/dashboard.
     */
    public function redirectSecureRootAction()
    {
        return $this->redirect($this->generateUrl('mautic_dashboard_index'), 301);
    }

    /**
     * Redirects controller if not ajax or retrieves html output for ajax request.
     *
     * @param array $args [returnUrl, viewParameters, contentTemplate, passthroughVars, flashes, forwardController]
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postActionRedirect($args = [])
    {
        $returnUrl = array_key_exists('returnUrl', $args) ? $args['returnUrl'] : $this->generateUrl('mautic_dashboard_index');
        $flashes   = array_key_exists('flashes', $args) ? $args['flashes'] : [];

        //forward the controller by default
        $args['forwardController'] = (array_key_exists('forwardController', $args)) ? $args['forwardController'] : true;

        //set flashes
        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $this->addFlash(
                    $flash['msg'],
                    !empty($flash['msgVars']) ? $flash['msgVars'] : [],
                    !empty($flash['type']) ? $flash['type'] : 'notice',
                    !empty($flash['domain']) ? $flash['domain'] : 'flashes'
                );
            }
        }

        if (isset($args['passthroughVars']['closeModal'])) {
            $args['passthroughVars']['updateMainContent'] = true;
        }

        if (!$this->request->isXmlHttpRequest() || !empty($args['ignoreAjax'])) {
            $code = (isset($args['responseCode'])) ? $args['responseCode'] : 302;

            return $this->redirect($returnUrl, $code);
        }

        //load by ajax
        return $this->ajaxAction($args);
    }

    /**
     * Generates html for ajax request.
     *
     * @param array $args [parameters, contentTemplate, passthroughVars, forwardController]
     *
     * @return JsonResponse
     */
    public function ajaxAction($args = [])
    {
        defined('MAUTIC_AJAX_VIEW') || define('MAUTIC_AJAX_VIEW', 1);

        $parameters      = array_key_exists('viewParameters', $args) ? $args['viewParameters'] : [];
        $contentTemplate = array_key_exists('contentTemplate', $args) ? $args['contentTemplate'] : '';
        $passthrough     = array_key_exists('passthroughVars', $args) ? $args['passthroughVars'] : [];
        $forward         = array_key_exists('forwardController', $args) ? $args['forwardController'] : false;
        $code            = array_key_exists('responseCode', $args) ? $args['responseCode'] : 200;

        /*
         * Return json response if this is a modal
         */
        if (!empty($passthrough['closeModal']) && empty($passthrough['updateModalContent']) && empty($passthrough['updateMainContent'])) {
            return new JsonResponse($passthrough);
        }

        //set the route to the returnUrl
        if (empty($passthrough['route']) && !empty($args['returnUrl'])) {
            $passthrough['route'] = $args['returnUrl'];
        }

        if (!empty($passthrough['route'])) {
            // Add the ajax route to the request so that the desired route is fed to plugins rather than the current request
            $baseUrl       = $this->request->getBaseUrl();
            $routePath     = str_replace($baseUrl, '', $passthrough['route']);
            $ajaxRouteName = false;

            try {
                $routeParams   = $this->get('router')->match($routePath);
                $ajaxRouteName = $routeParams['_route'];

                $this->request->attributes->set('ajaxRoute',
                    [
                        '_route'        => $ajaxRouteName,
                        '_route_params' => $routeParams,
                    ]
                );
            } catch (\Exception $e) {
                //do nothing
            }

            //breadcrumbs may fail as it will retrieve the crumb path for currently loaded URI so we must override
            $this->request->query->set('overrideRouteUri', $passthrough['route']);
            if ($ajaxRouteName) {
                if (isset($routeParams['objectAction'])) {
                    //action urls share same route name so tack on the action to differentiate
                    $ajaxRouteName .= "|{$routeParams['objectAction']}";
                }
                $this->request->query->set('overrideRouteName', $ajaxRouteName);
            }
        }

        //Ajax call so respond with json
        $newContent = '';
        if ($contentTemplate) {
            if ($forward) {
                //the content is from another controller action so we must retrieve the response from it instead of
                //directly parsing the template
                $query              = ['ignoreAjax' => true, 'request' => $this->request, 'subrequest' => true];
                $newContentResponse = $this->forward($contentTemplate, $parameters, $query);
                if ($newContentResponse instanceof RedirectResponse) {
                    $passthrough['redirect'] = $newContentResponse->getTargetUrl();
                    $passthrough['route']    = false;
                } else {
                    $newContent = $newContentResponse->getContent();
                }
            } else {
                $GLOBALS['MAUTIC_AJAX_DIRECT_RENDER'] = 1; // for error handling
                $newContent                           = $this->renderView($contentTemplate, $parameters);

                unset($GLOBALS['MAUTIC_AJAX_DIRECT_RENDER']);
            }
        }

        //there was a redirect within the controller leading to a double call of this function so just return the content
        //to prevent newContent from being json
        if ($this->request->get('ignoreAjax', false)) {
            return new Response($newContent, $code);
        }

        //render flashes
        $passthrough['flashes'] = $this->getFlashContent();

        if (!defined('MAUTIC_INSTALLER')) {
            // Prevent error in case installer is loaded via index_dev.php
            $passthrough['notifications'] = $this->getNotificationContent();
        }

        //render browser notifications
        $passthrough['browserNotifications'] = $this->get('session')->get('mautic.browser.notifications', []);
        $this->get('session')->set('mautic.browser.notifications', []);

        $tmpl = (isset($parameters['tmpl'])) ? $parameters['tmpl'] : $this->request->get('tmpl', 'index');
        if ($tmpl == 'index') {
            $updatedContent = [];
            if (!empty($newContent)) {
                $updatedContent['newContent'] = $newContent;
            }

            $dataArray = array_merge(
                $passthrough,
                $updatedContent
            );
        } else {
            //just retrieve the content
            $dataArray = array_merge(
                $passthrough,
                ['newContent' => $newContent]
            );
        }

        if ($newContent instanceof Response) {
            $response = $newContent;
        } else {
            $response = new JsonResponse($dataArray, $code);
        }

        return $response;
    }

    /**
     * Get's the content of error page.
     *
     * @param \Exception $e
     *
     * @return Response
     */
    public function renderException(\Exception $e)
    {
        $exception  = FlattenException::create($e, $e->getCode(), $this->request->headers->all());
        $parameters = ['request' => $this->request, 'exception' => $exception];
        $query      = ['ignoreAjax' => true, 'request' => $this->request, 'subrequest' => true];

        return $this->forward('MauticCoreBundle:Exception:show', $parameters, $query);
    }

    /**
     * Executes an action defined in route.
     *
     * @param string $objectAction
     * @param int    $objectId
     * @param int    $objectSubId
     * @param string $objectModel
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeAction($objectAction, $objectId = 0, $objectSubId = 0, $objectModel = '')
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($objectId, $objectModel);
        }

        return $this->notFound();
    }

    /**
     * Generates access denied message.
     *
     * @param bool   $batch Flag if a batch action is being performed
     * @param string $msg   Message that is logged
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|array
     *
     * @throws AccessDeniedHttpException
     */
    public function accessDenied($batch = false, $msg = 'mautic.core.url.error.401')
    {
        $anonymous = $this->get('mautic.security')->isAnonymous();

        if ($anonymous || !$batch) {
            throw new AccessDeniedHttpException(
                $this->translator->trans($msg,
                    [
                        '%url%' => $this->request->getRequestUri(),
                    ]
                )
            );
        }

        if ($batch) {
            return [
                'type' => 'error',
                'msg'  => $this->translator->trans('mautic.core.error.accessdenied', [], 'flashes'),
            ];
        }
    }

    /**
     * Generate 404 not found message.
     *
     * @param string $msg
     *
     * @return Response
     */
    public function notFound($msg = 'mautic.core.url.error.404')
    {
        return $this->renderException(
            new NotFoundHttpException(
                $this->translator->trans($msg,
                    [
                        '%url%' => $this->request->getRequestUri(),
                    ]
                )
            )
        );
    }

    /**
     * Returns a json encoded access denied error for modal windows.
     *
     * @param string $msg
     *
     * @return JsonResponse
     */
    public function modalAccessDenied($msg = 'mautic.core.error.accessdenied')
    {
        return new JsonResponse([
            'error' => $this->translator->trans($msg, [], 'flashes'),
        ]);
    }

    /**
     * Updates list filters, order, limit.
     *
     * @param null $name
     */
    protected function setListFilters($name = null)
    {
        $session = $this->get('session');

        if (null === $name) {
            $name = InputHelper::clean($this->request->query->get('name'));
        }
        $name = 'mautic.'.$name;

        if (!empty($name)) {
            if ($this->request->query->has('orderby')) {
                $orderBy = InputHelper::clean($this->request->query->get('orderby'), true);
                $dir     = $session->get("$name.orderbydir", 'ASC');
                $dir     = ($dir == 'ASC') ? 'DESC' : 'ASC';
                $session->set("$name.orderby", $orderBy);
                $session->set("$name.orderbydir", $dir);
            }

            if ($this->request->query->has('limit')) {
                $limit = InputHelper::int($this->request->query->get('limit'));
                $session->set("$name.limit", $limit);
            }

            if ($this->request->query->has('filterby')) {
                $filter  = InputHelper::clean($this->request->query->get('filterby'), true);
                $value   = InputHelper::clean($this->request->query->get('value'), true);
                $filters = $session->get("$name.filters", []);

                if ($value == '') {
                    if (isset($filters[$filter])) {
                        unset($filters[$filter]);
                    }
                } else {
                    $filters[$filter] = [
                        'column' => $filter,
                        'expr'   => 'like',
                        'value'  => $value,
                        'strict' => false,
                    ];
                }

                $session->set("$name.filters", $filters);
            }
        }
    }

    /**
     * Renders flashes' HTML.
     *
     * @return string
     */
    protected function getFlashContent()
    {
        return $this->renderView('MauticCoreBundle:Notification:flash_messages.html.php');
    }

    /**
     * Renders notification info for ajax.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getNotificationContent(Request $request = null)
    {
        if ($request == null) {
            $request = $this->request;
        }

        $afterId = $request->get('mauticLastNotificationId', null);

        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->getModel('core.notification');

        list($notifications, $showNewIndicator, $updateMessage) = $model->getNotificationContent($afterId);

        $lastNotification = reset($notifications);

        return [
            'content' => ($notifications || $updateMessage) ? $this->renderView('MauticCoreBundle:Notification:notification_messages.html.php', [
                'notifications' => $notifications,
                'updateMessage' => $updateMessage,
            ]) : '',
            'lastId'              => (!empty($lastNotification)) ? $lastNotification['id'] : $afterId,
            'hasNewNotifications' => $showNewIndicator,
            'updateAvailable'     => (!empty($updateMessage)),
        ];
    }

    /**
     * @param                $message
     * @param null           $type
     * @param bool|true      $isRead
     * @param null           $header
     * @param null           $iconClass
     * @param \DateTime|null $datetime
     */
    public function addNotification($message, $type = null, $isRead = true, $header = null, $iconClass = null, \DateTime $datetime = null)
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $notificationModel */
        $notificationModel = $this->getModel('core.notification');
        $notificationModel->addNotification($message, $type, $isRead, $header, $iconClass, $datetime);
    }

    /**
     * @param        $message
     * @param array  $messageVars
     * @param string $type
     * @param string $domain
     * @param bool   $addNotification
     */
    public function addFlash($message, $messageVars = [], $type = 'notice', $domain = 'flashes', $addNotification = false)
    {
        if ($domain == null) {
            $domain = 'flashes';
        }

        if ($domain === false) {
            //message is already translated
            $translatedMessage = $message;
        } else {
            if (isset($messageVars['pluralCount'])) {
                $translatedMessage = $this->get('translator')->transChoice($message, $messageVars['pluralCount'], $messageVars, $domain);
            } else {
                $translatedMessage = $this->get('translator')->trans($message, $messageVars, $domain);
            }
        }

        $this->get('session')->getFlashBag()->add($type, $translatedMessage);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($type) {
                case 'warning':
                    $iconClass = 'text-warning fa-exclamation-triangle';
                    break;
                case 'error':
                    $iconClass = 'text-danger fa-exclamation-circle';
                    break;
                case 'notice':
                    $iconClass = 'fa-info-circle';
                default:
                    break;
            }

            //If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->request->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->addNotification($translatedMessage, null, $isRead, null, $iconClass);
        }
    }

    /**
     * @param        $message
     * @param array  $messageVars
     * @param string $domain
     * @param null   $title
     * @param null   $icon
     * @param bool   $addNotification
     * @param string $type
     */
    public function addBrowserNotification($message, $messageVars = [], $domain = 'flashes', $title = null, $icon = null, $addNotification = true, $type = 'notice')
    {
        if ($domain == null) {
            $domain = 'flashes';
        }

        $translator = $this->translator;

        if ($domain === false) {
            //message is already translated
            $translatedMessage = $message;
        } else {
            if (isset($messageVars['pluralCount'])) {
                $translatedMessage = $translator->transChoice($message, $messageVars['pluralCount'], $messageVars, $domain);
            } else {
                $translatedMessage = $translator->trans($message, $messageVars, $domain);
            }
        }

        if ($title !== null) {
            $title = $translator->trans($title);
        } else {
            $title = 'Mautic';
        }

        if ($icon == null) {
            $icon = 'media/images/favicon.ico';
        }

        if (strpos($icon, 'http') !== 0) {
            $assetHelper = $this->factory->getHelper('template.assets');
            $icon        = $assetHelper->getUrl($icon, null, null, true);
        }

        $session                = $this->get('session');
        $browserNotifications   = $session->get('mautic.browser.notifications', []);
        $browserNotifications[] = [
            'message' => $translatedMessage,
            'title'   => $title,
            'icon'    => $icon,
        ];

        $session->set('mautic.browser.notifications', $browserNotifications);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($type) {
                case 'warning':
                    $iconClass = 'text-warning fa-exclamation-triangle';
                    break;
                case 'error':
                    $iconClass = 'text-danger fa-exclamation-circle';
                    break;
                case 'notice':
                    $iconClass = 'fa-info-circle';
                default:
                    break;
            }

            //If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->request->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->addNotification($translatedMessage, null, $isRead, null, $iconClass);
        }
    }

    /**
     * @param array $toExport
     * @param       $type
     * @param       $filename
     *
     * @return StreamedResponse
     */
    public function exportResultsAs($toExport, $type, $filename)
    {
        if (!in_array($type, ['csv', 'xlsx'])) {
            throw new \InvalidArgumentException($this->translator->trans('mautic.error.invalid.export.type', ['%type%' => $type]));
        }

        if ($toExport instanceof \Iterator) {
            $sourceIterator = new IteratorSourceIterator($toExport);
        } else {
            $sourceIterator = new ArraySourceIterator($toExport);
        }

        $dateFormat  = $this->coreParametersHelper->getParameter('date_format_dateonly');
        $dateFormat  = str_replace('--', '-', preg_replace('/[^a-zA-Z]/', '-', $dateFormat));
        $writer      = $type === 'xlsx' ? new XlsWriter('php://output') : new CsvWriter('php://output');
        $contentType = $type === 'xlsx' ? 'application/vnd.ms-excel' : 'text/csv';
        $filename    = strtolower($filename.'_'.((new \DateTime())->format($dateFormat)).'.'.$type);
        $handler     = Handler::create($sourceIterator, $writer);

        return new StreamedResponse(function () use ($handler, $sourceIterator, $writer) {
            $handler->export();
        }, 200, ['Content-Type' => $contentType, 'Content-Disposition' => sprintf('attachment; filename=%s', $filename)]);
    }

    /**
     * Standard function to generate an array of data via any model's "getEntities" method.
     *
     * Overwrite in your controller if required.
     *
     * @param AbstractCommonModel $model
     * @param array               $args
     * @param callable|null       $resultsCallback
     * @param int|null            $start
     *
     * @return array
     */
    protected function getDataForExport(AbstractCommonModel $model, array $args, callable $resultsCallback = null, $start = 0)
    {
        $data = new DataExporterHelper();

        return $data->getDataForExport($start, $model, $args, $resultsCallback);
    }
}
