<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class CommonController
 *
 * @package Mautic\CoreBundle\Controller
 */
class CommonController extends Controller implements MauticController
{
    /**
     * @var \Mautic\CoreBundle\Factory\MauticFactory
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
     * @param $args
     * @return JsonResponse|Response
     */
    function delegateView($args)
    {
        if (!is_array($args)) {
            $args = array(
                'contentTemplate' => $args,
                'passthroughVars' => array(
                    'mauticContent'   => strtolower($this->request->get('bundle'))
                )
            );
        }

        if ($this->request->isXmlHttpRequest() && !$this->request->get('ignoreAjax', false)) {
            return $this->ajaxAction($args);
        } else {
            $parameters = (isset($args['viewParameters'])) ? $args['viewParameters'] : array();
            $template   = $args['contentTemplate'];
            return $this->render($template, $parameters);
        }
    }

    /**
     * Redirects URLs with trailing slashes in order to prevent 404s
     *
     * @param Request $request
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
     * Redirects controller if not ajax or retrieves html output for ajax request
     *
     * @param array $args [returnUrl, viewParameters, contentTemplate, passthroughVars, flashes, forwardController]
     */
    public function postActionRedirect($args = array()) {
        $returnUrl = array_key_exists('returnUrl', $args) ? $args['returnUrl'] : $this->generateUrl('mautic_dashboard_index');
        $flashes   = array_key_exists('flashes', $args) ? $args['flashes'] : array();

        //forward the controller by default
        $args['forwardController'] = (array_key_exists('forwardController', $args)) ? $args['forwardController'] : true;

        //set flashes
        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $this->factory->getSession()->getFlashBag()->add(
                    $flash['type'],
                    $this->get('translator')->trans(
                        $flash['msg'],
                        (!empty($flash['msgVars']) ? $flash['msgVars'] : array()),
                        'flashes'
                    )
                );
            }
        }

        if (!$this->request->isXmlHttpRequest()) {
            $code = (isset($args['responseCode'])) ? $args['responseCode'] : 302;
            return $this->redirect($returnUrl, $code);
        } else {
            //load by ajax
            return $this->ajaxAction($args);
        }
    }

    /**
     * Generates html for ajax request
     *
     * @param array $args [parameters, contentTemplate, passthroughVars, forwardController]
     * @return JsonResponse
     */
    public function ajaxAction($args = array()) {
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
            $query = array("ignoreAjax" => true, 'request' => $this->request);
            $newContentResponse = $this->forward($contentTemplate, $parameters, $query);
            $newContent         = $newContentResponse->getContent();
        } else {
            $newContent  = $this->renderView($contentTemplate, $parameters);
        }

        //there was a redirect within the controller leading to a double call of this function so just return the content
        //to prevent newContent from being json
        if ($this->request->get('ignoreAjax', false)) {
            $response = new Response();
            $response->setContent($newContent);
            return $response;
        }

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
            if (!empty($newContent))
                $updatedContent['newContent'] = $newContent;

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
        $code      = (isset($args['responseCode'])) ? $args['responseCode'] : 200;
        $response  = new JsonResponse($dataArray, $code);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }

    /**
     * Executes an action defined in route
     *
     * @param     $objectAction
     * @param int $objectId
     * @return Response
     */
    public function executeAction($objectAction, $objectId = 0, $objectSubId = 0, $objectModel = '') {
        if (method_exists($this, "{$objectAction}Action")) {

            return $this->{"{$objectAction}Action"}($objectId, $objectModel);
        } else {
            return $this->accessDenied();
        }
    }

    /**
     * Generates access denied message
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function accessDenied($msg = 'mautic.core.error.accessdenied')
    {
        $user = $this->factory->getUser();
        if (!$user instanceof User && !$user->getId()) {
            throw new AccessDeniedHttpException($this->get('translator')->trans('mautic.core.url.error.401'));
        } else {
            return $this->postActionRedirect(array(
                'returnUrl'       => $this->generateUrl('mautic_dashboard_index'),
                'contentTemplate' => 'MauticCoreBundle:Default:index',
                'passthroughVars' => array(
                    'activeLink' => '#mautic_dashboard_index',
                    'route'      => $this->generateUrl('mautic_dashboard_index')
                ),
                'flashes'         => array(array(
                    'type' => 'error',
                    'msg'  => $msg
                ))
            ));
        }
    }

    /**
     * Clear the application cache and run the warmup routine for the current environment
     *
     * @return void
     */
    public function clearCache()
    {
        $input       = new ArgvInput(array('console', 'cache:clear', '--env=' . $this->factory->getEnvironment()));
        $application = new Application($this->get('kernel'));
        $application->setAutoExit(false);
        $application->run($input);
    }
}
