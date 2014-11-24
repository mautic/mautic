<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Event\CommandListEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonController
{

    /**
     * @param array $dataArray
     *
     * @return JsonResponse
     * @throws \Exception
     */
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
    public function delegateAjaxAction()
    {
        //process ajax actions
        $securityContext = $this->factory->getSecurityContext();
        $action          = (empty($ajaxAction)) ? $this->request->get("action") : $ajaxAction;

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if (strpos($action, ":") !== false) {
                //call the specified bundle's ajax action
                $parts = explode(":", $action);
                if (count($parts) == 2) {
                    $bundle     = ucfirst($parts[0]);
                    $action     = $parts[1];

                    if (class_exists('Mautic\\' . $bundle . 'Bundle\\Controller\\AjaxController')) {
                        return $this->forward("Mautic{$bundle}Bundle:Ajax:executeAjax", array(
                            'action'  => $action,
                            //forward the request as well as Symfony creates a subrequest without GET/POST
                            'request' => $this->request
                        ));
                    }
                }
            }

            return $this->executeAjaxAction($action, $this->request);
        }

        return $this->sendJsonResponse(array('success' => 0));
    }

    /**
     * @param string  $action
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function executeAjaxAction($action, Request $request)
    {
        if (method_exists($this, "{$action}Action")) {
            return $this->{"{$action}Action"}($request);
        }

        return $this->sendJsonResponse(array('success' => 0));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function setTableLimitAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $name  = InputHelper::clean($request->request->get("name"));
        $limit = InputHelper::int($request->request->get("limit"));
        if (!empty($name)) {
            $this->get("session")->set("mautic.$name.limit", $limit);
            $dataArray['success'] = 1;
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function setTableFilterAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $name   = InputHelper::clean($request->request->get("name"));
        $filter = InputHelper::clean($request->request->get("filterby"));
        $value  = InputHelper::clean($request->request->get("value"));
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

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function globalSearchAction(Request $request)
    {
        $dataArray = array('success' => 1);
        $searchStr = InputHelper::clean($request->query->get("global_search", ""));
        $this->factory->getSession()->set('mautic.global_search', $searchStr);

        $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
        $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);

        $dataArray['newContent'] = $this->renderView('MauticCoreBundle:Default:globalsearchresults.html.php',
            array('results' => $event->getResults())
        );
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function commandListAction(Request $request)
    {
        $model      = InputHelper::clean($request->query->get('model'));
        $commands   = $this->factory->getModel($model)->getCommandList();
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

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function globalCommandListAction(Request $request)
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

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function togglePanelAction(Request $request)
    {
        $panel     = InputHelper::clean($request->request->get("panel", "left"));
        $status    = $this->get("session")->get("{$panel}-panel", "default");
        $newStatus = ($status == "unpinned") ? "default" : "unpinned";
        $this->get("session")->set("{$panel}-panel", $newStatus);
        $dataArray = array('success' => 1);
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function togglePublishStatusAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $name   = InputHelper::clean($request->request->get('model'));
        if (strpos($name, '.') === false) {
            $name = "$name.$name";
        }
        $id     = InputHelper::int($request->request->get('id'));
        $model  = $this->factory->getModel($name);

        $post = $request->request->all();
        unset($post['model'], $post['id'], $post['action']);
        if (!empty($post)) {
            $extra = http_build_query($post);
        } else {
            $extra = '';
        }

        $entity = $model->getEntity($id);
        if ($entity !== null) {
            $permissionBase = $model->getPermissionBase();

            if ($this->factory->getSecurity()->hasEntityAccess(
                $permissionBase . ':publishown',
                $permissionBase . ':publishother',
                $entity->getCreatedBy()
            )
            ) {
                $dataArray['success'] = 1;
                //toggle permission state
                $model->togglePublishStatus($entity);
                //get updated icon HTML
                $html = $this->renderView('MauticCoreBundle:Helper:publishstatus.html.php', array(
                    'item'       => $entity,
                    'model'      => $name,
                    'extra'      => $extra

                ));
                $dataArray['statusHtml'] = $html;
            }
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Unlock an entity locked by the current user
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function unlockEntityAction(Request $request)
    {
        $dataArray   = array('success' => 0);
        $name        = InputHelper::clean($request->request->get('model'));
        $id          = InputHelper::int($request->request->get('id'));
        $extra       = InputHelper::clean($request->request->get('parameter'));
        $model       = $this->factory->getModel($name);
        $entity      = $model->getEntity($id);
        $currentUser = $this->factory->getUser();
        $checkedOut  = $entity->getCheckedOutBy();

        if ($entity !== null && !empty($checkedOut) && $checkedOut->getId() === $currentUser->getId()) {
            //entity exists, is checked out, and is checked out by the current user so go ahead and unlock
            $model->unlockEntity($entity, $extra);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }
}
