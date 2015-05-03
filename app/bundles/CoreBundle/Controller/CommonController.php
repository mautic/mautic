<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Templating\DelegatingEngine;

/**
 * Class CommonController
 */
class CommonController extends Controller implements MauticController
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param MauticFactory $factory
     *
     * @return void
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
    }

    /**
     * Determines if ajax content should be returned or direct content (page refresh)
     *
     * @param array $args
     *
     * @return JsonResponse|Response
     */
    public function delegateView($args)
    {
        if (!is_array($args)) {
            $args = array(
                'contentTemplate' => $args,
                'passthroughVars' => array(
                    'mauticContent'   => strtolower($this->request->get('bundle'))
                )
            );
        }

        if (isset($args['passthroughVars']['route'])) {
            $args['viewParameters']['currentRoute'] = $args['passthroughVars']['route'];
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction($args);
        }

        $parameters = (isset($args['viewParameters'])) ? $args['viewParameters'] : array();
        $template   = $args['contentTemplate'];

        return $this->render($template, $parameters);
    }

    /**
     * Determines if a redirect response should be returned or a Json response directing the ajax call to force a page
     * refresh
     *
     * @param $url
     */
    public function delegateRedirect($url)
    {
        if ($this->request->isXmlHttpRequest()) {
            return new JsonResponse(array('redirect' => $url));
        } else {
            return $this->redirect($url);
        }
    }

    /**
     * Redirects URLs with trailing slashes in order to prevent 404s
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
     * Redirects /s and /s/ to /s/dashboard
     */
    public function redirectSecureRootAction()
    {
        return $this->redirect($this->generateUrl('mautic_dashboard_index'), 301);
    }

    /**
     * Redirects controller if not ajax or retrieves html output for ajax request
     *
     * @param array $args [returnUrl, viewParameters, contentTemplate, passthroughVars, flashes, forwardController]
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postActionRedirect($args = array())
    {
        $returnUrl = array_key_exists('returnUrl', $args) ? $args['returnUrl'] : $this->generateUrl('mautic_dashboard_index');
        $flashes   = array_key_exists('flashes', $args) ? $args['flashes'] : array();

        //forward the controller by default
        $args['forwardController'] = (array_key_exists('forwardController', $args)) ? $args['forwardController'] : true;

        //set flashes
        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $this->addFlash(
                    $flash['msg'],
                    !empty($flash['msgVars']) ? $flash['msgVars'] : array(),
                    !empty($flash['type']) ? $flash['type'] : 'notice',
                    !empty($flash['domain']) ? $flash['domain'] : 'flashes'
                );
            }
        }

        if (!$this->request->isXmlHttpRequest() || !empty($args['ignoreAjax'])) {
            $code = (isset($args['responseCode'])) ? $args['responseCode'] : 302;
            return $this->redirect($returnUrl, $code);
        }

        //load by ajax
        return $this->ajaxAction($args);
    }

    /**
     * Generates html for ajax request
     *
     * @param array $args [parameters, contentTemplate, passthroughVars, forwardController]
     *
     * @return JsonResponse
     */
    public function ajaxAction($args = array())
    {
        $parameters      = array_key_exists('viewParameters', $args) ? $args['viewParameters'] : array();
        $contentTemplate = array_key_exists('contentTemplate', $args) ? $args['contentTemplate'] : '';
        $passthrough     = array_key_exists('passthroughVars', $args) ? $args['passthroughVars'] : array();
        $forward         = array_key_exists('forwardController', $args) ? $args['forwardController'] : false;

        //set the route to the returnUrl
        if (empty($passthrough["route"]) && !empty($args["returnUrl"])) {
            $passthrough["route"] = $args["returnUrl"];
        }

        //Ajax call so respond with json
        if ($forward) {
            //the content is from another controller action so we must retrieve the response from it instead of
            //directly parsing the template
            $query              = array("ignoreAjax" => true, 'request' => $this->request, 'subrequest' => true);
            $newContentResponse = $this->forward($contentTemplate, $parameters, $query);
            $newContent         = $newContentResponse->getContent();
        } else {
            $newContent = $this->renderView($contentTemplate, $parameters);
        }

        //there was a redirect within the controller leading to a double call of this function so just return the content
        //to prevent newContent from being json
        if ($this->request->get('ignoreAjax', false)) {
            $response = new Response();
            $response->setContent($newContent);
            return $response;
        }

        //render flashes
        $passthrough['flashes'] = $this->getFlashContent();

        if (!defined('MAUTIC_INSTALLER')) {
            // Prevent error in case installer is loaded via index_dev.php
            $passthrough['notifications'] = $this->getNotificationContent();
        }

        //render browser notifications
        $passthrough['browserNotifications'] = $this->factory->getSession()->get('mautic.browser.notifications', array());
        $this->factory->getSession()->set('mautic.browser.notifications', array());

        $tmpl = (isset($parameters['tmpl'])) ? $parameters['tmpl'] : $this->request->get('tmpl', 'index');
        if ($tmpl == 'index') {
            if (!empty($passthrough["route"])) {
                //breadcrumbs may fail as it will retrieve the crumb path for currently loaded URI so we must override
                $this->request->query->set("overrideRouteUri", $passthrough["route"]);

                //if the URL has a query built into it, breadcrumbs may fail matching
                //so let's try to find it by the route name which will be the extras["routeName"] of the menu item
                $baseUrl = $this->request->getBaseUrl();
                $routePath = str_replace($baseUrl, '', $passthrough["route"]);
                try {
                    $routeParams = $this->get('router')->match($routePath);
                    $routeName   = $routeParams["_route"];
                    if (isset($routeParams["objectAction"])) {
                        //action urls share same route name so tack on the action to differentiate
                        $routeName .= "|{$routeParams["objectAction"]}";
                    }
                    $this->request->query->set("overrideRouteName", $routeName);
                } catch (\Exception $e) {
                    //do nothing
                }
            }

            $updatedContent = array();
            if (!empty($newContent)) {
                $updatedContent['newContent'] = $newContent;
            }

            $dataArray = array_merge(
                $updatedContent,
                $passthrough
            );
        } else {
            //just retrieve the content
            $dataArray = array_merge(
                array('newContent'  => $newContent),
                $passthrough
            );
        }

        $code = (isset($args['responseCode'])) ? $args['responseCode'] : 200;

        if ($newContent instanceof Response) {
            $response = $newContent;
        } else {
            $response = new JsonResponse($dataArray, $code);
        }

        //$response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }

    /**
     * Get's the content of error page
     *
     * @param \Exception $e
     *
     * @return Response
     */
    public function renderException(\Exception $e)
    {
        $exception   = FlattenException::create($e, $e->getCode(), $this->request->headers->all());
        $parameters  = array('request' => $this->request, 'exception' => $exception);
        $query       = array("ignoreAjax" => true, 'request' => $this->request, 'subrequest' => true);

        return $this->forward('MauticCoreBundle:Exception:show', $parameters, $query);
    }

    /**
     * Executes an action defined in route
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

        return $this->accessDenied();
    }

    /**
     * Generates access denied message
     *
     * @param bool   $batch Flag if a batch action is being performed
     * @param string $msg   Message to display to the user
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|array
     * @throws AccessDeniedHttpException
     */
    public function accessDenied($batch = false, $msg = 'mautic.core.error.accessdenied')
    {
        $anonymous = $this->factory->getSecurity()->isAnonymous();

        if ($anonymous || !$batch) {
            throw new AccessDeniedHttpException($this->get('translator')->trans($msg, array(), 'flashes'));
        }

        if ($batch) {
            return array(
                'type' => 'error',
                'msg'  => $msg
            );
        }
    }

    /**
     * Returns a json encoded access denied error for modal windows
     *
     * @param string $msg
     *
     * @return JsonResponse
     */
    public function modalAccessDenied($msg = 'mautic.core.error.accessdenied')
    {
        return new JsonResponse(array(
            'error' => $this->factory->getTranslator()->trans($msg, array(), 'flashes')
        ));
    }

    /**
     * Clear the application cache and run the warmup routine for the current environment
     *
     * @param bool $noWarmup If true, will not run warmup routine
     *
     *
     * @return void
     */
    public function clearCache($noWarmup = false)
    {
        /** @var \Mautic\CoreBundle\Helper\CacheHelper $cacheHelper */
        $cacheHelper = $this->factory->getHelper('cache');
        $cacheHelper->clearCache($noWarmup);
    }

    /**
     * Delete's the file Symfony caches settings in
     */
    public function clearCacheFile()
    {
        /** @var \Mautic\CoreBundle\Helper\CacheHelper $cacheHelper */
        $cacheHelper = $this->factory->getHelper('cache');
        $cacheHelper->clearCacheFile();
    }

    /**
     * Updates list filters, order, limit
     *
     * @return void
     */
    protected function setListFilters()
    {
        $session = $this->factory->getSession();
        $name    = InputHelper::clean($this->request->query->get('name'));

        if (!empty($name)) {
            if ($this->request->query->has('orderby')) {
                $orderBy = InputHelper::clean($this->request->query->get('orderby'), true);
                $dir     = $this->get('session')->get("mautic.$name.orderbydir", 'ASC');
                $dir     = ($dir == 'ASC') ? 'DESC' : 'ASC';
                $session->set("mautic.$name.orderby", $orderBy);
                $session->set("mautic.$name.orderbydir", $dir);
            }

            if ($this->request->query->has('limit')) {
                $limit = InputHelper::int($this->request->query->get('limit'));
                $session->set("mautic.$name.limit", $limit);
            }

            if ($this->request->query->has('filterby')) {
                $filter  = InputHelper::clean($this->request->query->get("filterby"), true);
                $value   = InputHelper::clean($this->request->query->get("value"), true);
                $filters = $session->get("mautic.$name.filters", array());
                if (empty($value)) {
                    if (isset($filters[$filter])) {
                        unset($filters[$filter]);
                    }
                } else {
                    $filters[$filter] = array(
                        'column' => $filter,
                        'expr'   => 'like',
                        'value'  => $value,
                        'strict' => false
                    );
                }
                $session->set("mautic.$name.filters", $filters);
            }
        }
    }

    /**
     * Sets a specific theme for the form
     *
     * @param Form   $form
     * @param string $template
     * @param mixed  $theme
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function setFormTheme(Form $form, $template, $theme = null)
    {
        $formView = $form->createView();

        if (empty($theme)) {
            return $formView;
        }

        $templating = $this->factory->getTemplating();

        if ($templating instanceof DelegatingEngine) {
            $templating->getEngine($template)->get('form')->setTheme($formView, $theme);
        } else {
            $templating->get('form')->setTheme($formView, $theme);
        }

        return $formView;
    }

    /**
     * Renders flashes' HTML
     *
     * @return string
     */
    protected function getFlashContent()
    {
        return $this->renderView('MauticCoreBundle:Notification:flash_messages.html.php');
    }

    /**
     * Renders notification info for ajax
     *
     * @return string
     */
    protected function getNotificationContent(Request $request = null)
    {
        if ($request == null) {
            $request = $this->request;
        }

        $afterId = $request->get('mauticLastNotificationId', null);

        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->factory->getModel('core.notification');

        list($notifications, $showNewIndicator, $updateMessage) = $model->getNotificationContent($afterId);

        $lastNotification = reset($notifications);

        return array(
            'content' => ($notifications || $updateMessage) ? $this->renderView('MauticCoreBundle:Notification:notification_messages.html.php', array(
                'notifications' => $notifications,
                'updateMessage' => $updateMessage
            )) : '',
            'lastId'              => (!empty($lastNotification)) ? $lastNotification['id'] : $afterId,
            'hasNewNotifications' => $showNewIndicator,
            'updateAvailable'     => (!empty($updateMessage))
        );
    }

    /**
     * @param      $message
     * @param null $type
     * @param bool $isRead
     * @param null $header
     * @param null $iconClass
     */
    public function addNotification($message, $type = null, $isRead = true, $header = null, $iconClass = null, \DateTime $datetime = null)
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $notificationModel */
        $notificationModel = $this->factory->getModel('core.notification');
        $notificationModel->addNotification($message, $type, $isRead, $header, $iconClass, $datetime );
    }

    /**
     * @param        $message
     * @param array  $messageVars
     * @param string $type
     * @param string $domain
     * @param bool   $addNotification
     */
    public function addFlash($message, $messageVars = array(), $type = 'notice', $domain = 'flashes', $addNotification = true)
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

        $this->factory->getSession()->getFlashBag()->add($type, $translatedMessage);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($type) {
                case 'warning':
                    $iconClass = "text-warning fa-exclamation-triangle";
                    break;
                case 'error':
                    $iconClass = "text-danger fa-exclamation-circle";
                    break;
                case 'notice':
                    $iconClass = "fa-info-circle";
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
    public function addBrowserNotification($message, $messageVars = array(), $domain = 'flashes', $title = null, $icon = null, $addNotification = true, $type = 'notice')
    {
        if ($domain == null) {
            $domain = 'flashes';
        }

        $translator = $this->factory->getTranslator();

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

        $session                = $this->factory->getSession();
        $browserNotifications   = $session->get('mautic.browser.notifications', array());
        $browserNotifications[] = array(
            'message' => $translatedMessage,
            'title'   => $title,
            'icon'    => $icon
        );

        $session->set('mautic.browser.notifications', $browserNotifications);

        if (!defined('MAUTIC_INSTALLER') && $addNotification) {
            switch ($type) {
                case 'warning':
                    $iconClass = "text-warning fa-exclamation-triangle";
                    break;
                case 'error':
                    $iconClass = "text-danger fa-exclamation-circle";
                    break;
                case 'notice':
                    $iconClass = "fa-info-circle";
                default:
                    break;
            }

            //If the user has not interacted with the browser for the last 30 seconds, consider the message unread
            $lastActive = $this->request->get('mauticUserLastActive', 0);
            $isRead     = $lastActive > 30 ? 0 : 1;

            $this->addNotification($translatedMessage, null, $isRead, null, $iconClass);
        }
    }
}
