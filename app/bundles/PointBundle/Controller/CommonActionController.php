<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommonActionController extends CommonFormController
{

    protected $permissionName;
    protected $actionVar;
    protected $modelName;
    protected $formName;
    protected $templateVar;
    protected $mauticContent;
    protected $routeVar;

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $success     = 0;
        $valid       = $cancelled = false;
        $method      = $this->request->getMethod();
        $session     = $this->factory->getSession();

        if ($method == 'POST') {
            $pointAction = $this->request->request->get($this->actionVar);
            $actionType = $pointAction['type'];
        } else {
            $actionType = $this->request->query->get('type');
            $pointAction = array('type' => $actionType);
        }

        //ajax only for form fields
        if (!$actionType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'point:'.$this->permissionName.':editown',
                'point:'.$this->permissionName.':editother',
                'point:'.$this->permissionName.':create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the builder event
        $customComponents = $this->factory->getModel($this->modelName)->getCustomComponents();
        $form = $this->get('form.factory')->create($this->formName, $pointAction, array(
            'action'    => $this->generateUrl('mautic_'.$this->routeVar.'_action', array('objectAction' => 'new')),
            'settings'  => $customComponents['actions'][$actionType]
        ));

        $pointAction['settings'] = $customComponents['actions'][$actionType];

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new' . uniqid();

                    //save the properties to session
                    $actions          = $session->get('mautic.'.$this->actionVar.'.add');
                    $formData         = $form->getData();
                    $pointAction       = array_merge($pointAction, $formData);
                    $pointAction['id'] = $keyId;
                    if (empty($pointAction['name'])) {
                        //set it to the event default
                        $pointAction['name'] = $this->get('translator')->trans($pointAction['settings']['label']);
                    }
                    $actions[$keyId]  = $pointAction;
                    $session->set('mautic.'.$this->actionVar.'.add', $actions);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $actionType);
        if ($cancelled || $valid) {
            $tmpl                   = 'components';
            $viewParams['actions']  = $customComponents['actions'];
        } else {
            $tmpl     = 'action';
            $formView = $form->createView();
            $this->get('templating')->getEngine('MauticPointBundle:'.$this->templateVar.':index.html.php')->get('form')
                ->setTheme($formView, 'MauticPointBundle:PointComponent');
            $viewParams['form'] = $formView;
            $header = $pointAction['settings']['label'];
            $viewParams['actionHeader'] = $this->get('translator')->trans($header);
        }
        $viewParams['tmpl'] = $tmpl;

        $passthroughVars = array(
            'mauticContent' => $this->mauticContent,
            'success'       => $success,
            'target'        => '.bundle-side-inner-wrapper',
            'route'         => false
        );

        if (!empty($keyId) ) {
            //prevent undefined errors
            $class       = "\\Mautic\\PointBundle\\Entity\\{$this->entityClass}";
            $entity      = new $class();
            $blank       = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $template = (empty($pointAction['settings']['template'])) ? 'MauticPointBundle:Action:generic.html.php'
                : $pointAction['settings']['template'];


            $passthroughVars['actionId']   = $keyId;
            $passthroughVars['actionHtml'] = $this->renderView($template, array(
                'inForm'      => true,
                'action'      => $pointAction,
                'id'          => $keyId,
                'builderType' => $this->templateVar
            ));
        }

        return $this->ajaxAction(array(
            'contentTemplate' => 'MauticPointBundle:'.$this->templateVar.'Builder:' . $tmpl . '.html.php',
            'viewParameters'  => $viewParams,
            'passthroughVars' => $passthroughVars
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        $session    = $this->factory->getSession();
        $method     = $this->request->getMethod();
        $actions    = $session->get('mautic.'.$this->actionVar.'.add', array());
        $success    = 0;
        $valid      = $cancelled = false;
        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($pointAction !== null) {
            $actionType  = $pointAction['type'];

            //ajax only for form fields
            if (!$actionType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array(
                    'point:'.$this->permissionName.':editown',
                    'point:'.$this->permissionName.':editother',
                    'point:'.$this->permissionName.':create'
                ), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            $form = $this->get('form.factory')->create($this->formName, $pointAction, array(
                'action'   => $this->generateUrl('mautic_'.$this->routeVar.'_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
                'settings' => $pointAction['settings']
            ));

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //form is valid so process the data

                        //save the properties to session
                        $session           = $this->factory->getSession();
                        $actions           = $session->get('mautic.'.$this->actionVar.'.add');
                        $formData          = $form->getData();
                        //overwrite with updated data
                        $pointAction        = array_merge($actions[$objectId], $formData);
                        if (empty($pointAction['name'])) {
                            //set it to the event default
                            $pointAction['name'] = $this->get('translator')->trans($pointAction['settings']['label']);
                        }
                        $actions[$objectId] = $pointAction;
                        $session->set('mautic.'.$this->actionVar.'.add', $actions);

                        //generate HTML for the field
                        $keyId = $objectId;
                    }
                }
            }

            $viewParams = array('type' => $actionType);
            if ($cancelled || $valid) {
                $tmpl = 'components';

                //fire the form builder event
                $customComponents = $this->factory->getModel($this->modelName)->getCustomComponents();
                $viewParams['actions']  = $customComponents['actions'];
            } else {
                $tmpl     = 'action';
                $formView = $form->createView();
                $this->get('templating')->getEngine('MauticPointBundle:'.$this->templateVar.':index.html.php')->get('form')
                    ->setTheme($formView, 'MauticPointBundle:PointComponent');
                $viewParams['form']        = $formView;
                $viewParams['actionHeader'] = $this->get('translator')->trans($pointAction['settings']['label']);
            }
            $viewParams['tmpl'] = $tmpl;

            $passthroughVars = array(
                'mauticContent' => $this->mauticContent,
                'success'       => $success,
                'target'        => '.bundle-side-inner-wrapper',
                'route'         => false
            );

            if (!empty($keyId)) {
                $passthroughVars['actionId'] = $keyId;

                //prevent undefined errors
                $class      = "\\Mautic\\PointBundle\\Entity\\{$this->entityClass}";
                $entity     = new $class();
                $blank      = $entity->convertToArray();
                $pointAction = array_merge($blank, $pointAction);
                $template = (empty($pointAction['settings']['template'])) ? 'MauticPointBundle:Action:generic.html.php'
                    : $pointAction['settings']['template'];


                $passthroughVars['actionId']   = $keyId;
                $passthroughVars['actionHtml'] = $this->renderView($template, array(
                    'inForm'      => true,
                    'action'      => $pointAction,
                    'id'          => $keyId,
                    'builderType' => $this->templateVar
                ));
            }

            return $this->ajaxAction(array(
                'contentTemplate' => 'MauticPointBundle:'.$this->templateVar . 'Builder:' . $tmpl . '.html.php',
                'viewParameters'  => $viewParams,
                'passthroughVars' => $passthroughVars
            ));
        } else {
            $response  = new JsonResponse(array('success' => 0));
            $response->headers->set('Content-Length', strlen($response->getContent()));
            return $response;
        }
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        $session   = $this->factory->getSession();
        $actions   = $session->get('mautic.'.$this->actionVar.'.add', array());
        $delete    = $session->get('mautic.'.$this->actionVar.'.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'point:'.$this->permissionName.':editown',
                'point:'.$this->permissionName.':editother',
                'point:'.$this->permissionName.':create'
            ), 'MATCH_ONE')
        ){
            return $this->accessDenied();
        }

        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $pointAction !== null) {
            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.' . $this->actionVar . '.remove', $delete);
            }

            $template = (empty($pointAction['settings']['template'])) ? 'MauticPointBundle:Action:generic.html.php'
                : $pointAction['settings']['template'];

            //prevent undefined errors
            $class       = "\\Mautic\\PointBundle\\Entity\\{$this->entityClass}";
            $entity      = new $class();
            $blank       = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $dataArray = array(
                'mauticContent'  => $this->mauticContent,
                'success'        => 1,
                'target'         => '#action_' . $objectId,
                'route'          => false,
                'actionId'       => $objectId,
                'replaceContent' => true,
                'actionHtml'     => $this->renderView($template, array(
                    'inForm'      => true,
                    'action'      => $pointAction,
                    'id'          => $objectId,
                    'deleted'     => true,
                    'builderType' => $this->templateVar
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }

    /**
     * Undeletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function undeleteAction($objectId) {
        $session   = $this->factory->getSession();
        $actions   = $session->get('mautic.'.$this->actionVar.'.add', array());
        $delete    = $session->get('mautic.'.$this->actionVar.'.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array(
                'point:'.$this->permissionName.':editown',
                'point:'.$this->permissionName.':editother',
                'point:'.$this->permissionName.':create'
            ), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $pointAction !== null) {

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.' . $this->actionVar . '.remove', $delete);
            }

            $template = (empty($pointAction['settings']['template'])) ? 'MauticPointBundle:Action:generic.html.php'
                : $pointAction['settings']['template'];

            //prevent undefined errors
            $class       = "\\Mautic\\PointBundle\\Entity\\{$this->entityClass}";
            $entity      = new $class();
            $blank       = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $dataArray = array(
                'mauticContent'  => $this->mauticContent,
                'success'        => 1,
                'target'         => '#action_' . $objectId,
                'route'          => false,
                'actionId'       => $objectId,
                'replaceContent' => true,
                'actionHtml'     => $this->renderView($template, array(
                    'inForm'      => true,
                    'action'      => $pointAction,
                    'id'          => $objectId,
                    'deleted'     => false,
                    'builderType' => $this->templateVar
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }
}