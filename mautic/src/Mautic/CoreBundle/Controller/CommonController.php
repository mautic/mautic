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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CommonController
 *
 * @package Mautic\CoreBundle\Controller
 */
class CommonController extends Controller implements EventsController {

    /**
     * @param Request $request
     * @param         $returnUrl
     * @param null    $parameters
     * @param null    $contentTemplate
     * @param null    $passthrough
     * @param null    $overrideBundle
     */
    public function postAction(Request $request,
                                    $returnUrl,
                                    $parameters = null,
                                    $contentTemplate = null,
                                    $passthrough = null,
                                    $overrideBundle = null) {

        if (!$request->isXmlHttpRequest()) {
            return $this->redirect($returnUrl);
        } else {
            //load by ajax
            return $this->ajaxAction(
                $request,
                $parameters,
                $contentTemplate,
                $passthrough,
                $overrideBundle
            );
        }
    }

    /**
     * @param Request $request
     * @param array   $parameters
     * @param string  $contentTemplate
     * @param array   $passthrough
     * @param bool    $forward
     * @param bool    $overrideBundle
     * @return JsonResponse
     */
    public function ajaxAction(Request $request,
                               array $parameters = array(),
                               $contentTemplate  = "Default:index.html.php",
                               $passthrough      = array(),
                               $forward          = false,
                               $overrideBundle   = false
    ) {
        $bundle   = ($overrideBundle) ?: $request->get("bundle");

        if (!empty($passthrough["route"])) {
            //breadcrumbs may fail as it will retrieve the crumb path for currently loaded URI so we must override
            $request->query->set("overrideRouteUri", $passthrough["route"]);

            //if the URL has a query built into it, breadcrumbs may fail matching
            //so let's try to find it by the route name which will be the extras["routeName"] of the menu item
            $baseUrl = $request->getBaseUrl();
            $routePath = str_replace($baseUrl, '', $passthrough["route"]);
            try {
                $routeParams = $this->get('router')->match($routePath);
                $routeName   = $routeParams["_route"];
                if (isset($routeParams["objectAction"])) {
                    //action urls share same route name so tack on the action to differentiate
                    $routeName .= "|{$routeParams["objectAction"]}";
                }
                $request->query->set("overrideRouteName", $routeName);
            } catch (Exception $e) {
                //do nothing
            }
        }

        //Ajax call so respond with json
        if ($forward) {
            //the content is from another controller so we must retrieve the response from it
            $query = array("ignoreAjax" => true);
            $newContentResponse = $this->forward("Mautic{$bundle}Bundle:$contentTemplate", $parameters, $query);
            $newContent         = $newContentResponse->getContent();
        } else {
            $newContent  = $this->renderView("Mautic{$bundle}Bundle:$contentTemplate", $parameters);
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
     * @param Requets $request
     */
    protected function executeAjaxActions(Request $request) {
        //process ajax actions
        $success         = 0;
        $securityContext = $this->container->get('security.context');
        $action          = $request->get("ajaxAction");
        if( $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ) {
            switch ($action) {
                case "togglepanel":
                    $panel     = $request->get("panel", "left");
                    $status    = $this->get("session")->get("{$panel}-panel", 1);
                    $newStatus = ($status) ? 0 : 1;
                    $this->get("session")->set("{$panel}-panel", $newStatus);
                    $success = 1;
                    break;
                case "setorderby":
                    $name    = $request->get("name");
                    $orderBy = $request->get("orderby");
                    if (!empty($name) && !empty($orderBy)) {
                        $dir = $this->get("session")->get("$name.orderbydir", "ASC");
                        $dir = ($dir == "ASC") ? "DESC" : "ASC";
                        $this->get("session")->set("$name.orderby", $orderBy);
                        $this->get("session")->set("$name.orderbydir", $dir);
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
     * @param     $objectAction
     * @param int $objectId
     * @return Response
     */
    public function executeAction(Request $request, $objectAction, $objectId = 0) {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($objectId, $request);
        } else {
            $response = new Response();
            $response->setContent($this->get("translator")->trans("mautic.security.accessdenied"));
            return $response;
        }
    }
}