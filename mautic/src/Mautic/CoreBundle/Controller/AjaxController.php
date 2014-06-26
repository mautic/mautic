<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Event\CommandListEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AjaxController
 *
 * @package Mautic\CoreBundle\Controller
 */
class AjaxController extends CommonController
{

    protected function sendJsonResponse($dataArray)
    {
        $response  = new JsonResponse();
        $response->setData($dataArray);

        return $response;
    }

    /**
     * Executes an action requested via ajax
     *
     * @return JsonResponse
     */
    public function delegateAjaxAction(  )
    {
        //process ajax actions
        $securityContext = $this->container->get('security.context');
        $action          = (empty($ajaxAction)) ? $this->request->get("ajaxAction") : $ajaxAction;

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if (strpos($action, ":") !== false) {
                //call the specified bundle's ajax action
                $parts = explode(":", $action);
                if (count($parts) == 2) {
                    $bundle     = ucfirst($parts[0]);
                    $action     = $parts[1];

                    if (class_exists('Mautic\\' . $bundle . 'Bundle\\Controller' . '\\' . 'AjaxController')) {
                        return $this->forward("Mautic{$bundle}Bundle:Ajax:executeAjax", array(
                            'ajaxAction' => $action
                        ));
                    }
                }
            } else {
                return $this->executeAjaxAction($action);
            }
        }
        return $this->sendJsonResponse(array('success' => 0));
    }

    protected function executeAjaxAction($action)
    {
        if (method_exists($this, "{$action}Action")) {
            return $this->{"{$action}Action"}();
        } else {
            return $this->sendJsonResponse(array('success' => 0));
        }
    }

    protected function setTableOrderAction()
    {
        $dataArray = array('success' => 0);
        $name    = InputHelper::clean($this->request->request->get("name"));
        $orderBy = InputHelper::clean($this->request->request->get("orderby"));
        if (!empty($name) && !empty($orderBy)) {
            $dir = $this->get("session")->get("mautic.$name.orderbydir", "ASC");
            $dir = ($dir == "ASC") ? "DESC" : "ASC";
            $this->get("session")->set("mautic.$name.orderby", $orderBy);
            $this->get("session")->set("mautic.$name.orderbydir", $dir);
            $dataArray['success'] = 1;
        }
        return $this->sendJsonResponse($dataArray);
    }

    protected function setTableLimitAction()
    {
        $dataArray = array('success' => 0);
        $name  = InputHelper::clean($this->request->request->get("name"));
        $limit = InputHelper::int($this->request->request->get("limit"));
        if (!empty($name)) {
            $this->get("session")->set("mautic.$name.limit", $limit);
            $dataArray['success'] = 1;
        }
        return $this->sendJsonResponse($dataArray);
    }

    protected function setTableFilterAction()
    {
        $dataArray = array('success' => 0);
        $name   = InputHelper::clean($this->request->request->get("name"));
        $filter = InputHelper::clean($this->request->request->get("filterby"));
        $value  = InputHelper::clean($this->request->request->get("value"));
        if (!empty($name) && !empty($filter)) {
            $filters              = $this->get("session")->get("mautic.$name.filters", '');
            if (empty($value) && isset($filters[$filter])) {
                unset($filters[$filter]);
            } else {
                $filters[$filter] = array(
                    'column' => $filter,
                    'expr'   => 'like',
                    'value'  => $value,
                    'strict' => false
                );
            }
            $this->get("session")->set("mautic.$name.filters", $filters);
            $dataArray['success'] = 1;
        }
        return $this->sendJsonResponse($dataArray);
    }

    protected function globalSearchAction()
    {
        $dataArray = array('success' => 1);
        $searchStr = InputHelper::clean($this->request->query->get("global_search", ""));
        $this->get('session')->set('mautic.global_search', $searchStr);

        $event = new GlobalSearchEvent($searchStr);
        $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);

        $dataArray['newContent'] = $this->renderView('MauticCoreBundle:Default:globalsearchresults.html.php',
            array('results' => $event->getResults())
        );
        return $this->sendJsonResponse($dataArray);
    }

    protected function commandListAction()
    {
        $model      = InputHelper::clean($this->request->query->get('model'));
        $commands   = $this->get('mautic.factory')->getModel($model)->getCommandList();
        $dataArray  = array();
        $translator = $this->get('translator');
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                $k = $translator->trans($k);
                foreach ($c as $subc) {
                    $dataArray[] = array('value' => $k . ":" . $translator->trans($subc));
                }
            } else {
                $dataArray[] = array('value' => $translator->trans($c) . ":");
            }
        }
        sort($dataArray);
        return $this->sendJsonResponse($dataArray);
    }

    protected function globalCommandListAction()
    {
        $dispatcher = $this->get('event_dispatcher');
        $event = new CommandListEvent();
        $dispatcher->dispatch(CoreEvents::BUILD_COMMAND_LIST, $event);
        $allCommands = $event->getCommands();
        $translator  = $this->get('translator');
        $dataArray   = array();
        $dupChecker  = array();
        foreach ($allCommands as $header => $commands) {
            //@todo if/when figure out a way for typeahead dynamic headers
            //$header = $translator->trans($header);
            //$dataArray[$header] = array();
            foreach ($commands as $k => $c) {
                if (is_array($c)) {
                    $k = $translator->trans($k);
                    foreach ($c as $subc) {
                        $command = $k . ":" . $translator->trans($subc);
                        if (!in_array($command, $dupChecker)) {
                            $dataArray[] = array('value' => $command);
                            $dupChecker[] = $command;
                        }
                    }
                } else {
                    $command = $translator->trans($c) . ":";
                    if (!in_array($command, $dupChecker)) {
                        $dataArray[] = array('value' => $command);
                        $dupChecker[] = $command;
                    }
                }
            }
            //sort($dataArray[$header]);
        }
        //ksort($dataArray);
        sort($dataArray);
        return $this->sendJsonResponse($dataArray);
    }

    protected function togglePanelAction()
    {
        $panel     = InputHelper::clean($this->request->request->get("panel", "left"));
        $status    = $this->get("session")->get("{$panel}-panel", "default");
        $newStatus = ($status == "unpinned") ? "default" : "unpinned";
        $this->get("session")->set("{$panel}-panel", $newStatus);
        $dataArray = array('success' => 1);
        return $this->sendJsonResponse($dataArray);
    }

    protected function togglePublishStatusAction()
    {
        $dataArray = array('success' => 0);
        $name   = InputHelper::clean($this->request->request->get('model'));
        $id     = InputHelper::int($this->request->request->get('id'));
        $model  = $this->get('mautic.factory')->getModel($name);
        $entity = $model->getEntity($id);
        if ($entity !== null) {
            $permissionBase = $model->getPermissionBase();
            if ($this->get('mautic.security')->hasEntityAccess(
                $permissionBase . ':publishown',
                $permissionBase . ':publishother',
                $entity
            )
            ) {
                $dataArray['success'] = 1;
                //toggle permission state
                $model->togglePublishStatus($entity);
                $dateFormat = $this->get('mautic.factory')->getParam('date_format_full');
                //get updated icon HTML
                $html = $this->renderView('MauticCoreBundle:Helper:publishstatus.html.php',array(
                    'item'       => $entity,
                    'dateFormat' => $dateFormat,
                    'model'      => $name
                ));
                $dataArray['statusHtml'] = $html;
            }
        }
        return $this->sendJsonResponse($dataArray);
    }
}