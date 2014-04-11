<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CommonController
 *
 * @package Mautic\CoreBundle\Controller
 */
class CommonController extends Controller implements EventsController {
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request) {
        $this->request = $request;
    }

    /**
     * Redirects controller if not ajax or retrieves html output for ajax request
     *
     * @param array $args [returnUrl, viewParameters, contentTemplate, passthroughVars, flashes, forwardController]
     */
    public function postActionRedirect($args = array()) {
        $returnUrl = array_key_exists('returnUrl', $args) ? $args['returnUrl'] : $this->generateUrl('mautic_core_index');
        $flashes   = array_key_exists('flashes', $args) ? $args['flashes'] : array();

        //forward the controller by default
        $args['forwardController'] = (array_key_exists('forwardController', $args)) ? $args['forwardController'] : true;

        if (!empty($flashes)) {
            foreach ($flashes as $flash) {
                $this->get('session')->getFlashBag()->add(
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
            return $this->redirect($returnUrl);
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

        if (empty($contentTemplate)) {
            $contentTemplate = 'Mautic'. $this->request->get('bundle') . 'Bundle:Default:index.html.php';
        }

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

        //Ajax call so respond with json
        if ($forward) {
            //the content is from another controller action so we must retrieve the response from it instead of the
            //directly parsing the template
            $query = array("ignoreAjax" => true);
            $newContentResponse = $this->forward($contentTemplate, $parameters, $query);
            $newContent         = $newContentResponse->getContent();
        } else {
            $newContent  = $this->renderView($contentTemplate, $parameters);
        }

        $breadcrumbs = $this->renderView("MauticCoreBundle:Default:breadcrumbs.html.php", $parameters);
        $flashes     = $this->renderView("MauticCoreBundle:Default:flashes.html.php", $parameters);

        $response  = new JsonResponse();
        $dataArray = array_merge(
            array(
                'newContent'  => $newContent,
                'breadcrumbs' => $breadcrumbs,
                'flashes'     => $flashes
            ),
            $passthrough
        );
        $response->setData($dataArray);

        return $response;
    }

    /**
     * Executes an action requested via ajax
     *
     */
    protected function executeAjaxActions() {
        //process ajax actions
        $success         = 0;
        $securityContext = $this->container->get('security.context');
        $action          = $this->request->request->get("ajaxAction");
        if( $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ) {
            switch ($action) {
                case "togglepanel":
                    $panel     = $this->request->request->get("panel", "left");
                    $status    = $this->get("session")->get("{$panel}-panel", "default");
                    $newStatus = ($status == "unpinned") ? "default" : "unpinned";
                    $this->get("session")->set("{$panel}-panel", $newStatus);
                    $success = 1;
                    break;
                case "setorderby":
                    $name    = $this->request->request->get("name");
                    $orderBy = $this->request->request->get("orderby");
                    if (!empty($name) && !empty($orderBy)) {
                        $dir = $this->get("session")->get("mautic.$name.orderbydir", "ASC");
                        $dir = ($dir == "ASC") ? "DESC" : "ASC";
                        $this->get("session")->set("mautic.$name.orderby", $orderBy);
                        $this->get("session")->set("mautic.$name.orderbydir", $dir);
                        $success = 1;
                    }
                    break;
                default:
                    //ignore
                    break;
            }
        }
        $response  = new JsonResponse();
        $dataArray = array("success" => $success);
        $response->setData($dataArray);

        return $response;
    }

    /**
     * Executes an action defined in route
     *
     * @param     $objectAction
     * @param int $objectId
     * @return Response
     */
    public function executeAction($objectAction, $objectId = 0) {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($objectId);
        } else {
            return $this->accessDenied();
        }
    }

    /**
     * Generates access denied message
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function accessDenied()
    {
        return $this->postActionRedirect( array(
            'returnUrl'       => $this->generateUrl('mautic_core_index'),
            'contentTemplate' => 'MauticCoreBundle:Default:index',
            'passthroughVars'     =>    array(
                'activeLink' => '#mautic_core_index',
                'route'      => $this->generateUrl('mautic_core_index')
            ),
            'flashes'         => array(array(
                'type' => 'error',
                'msg'  => 'mautic.user.core.error.accessdenied'
            ))
        ));
    }
}