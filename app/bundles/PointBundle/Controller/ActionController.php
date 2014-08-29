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
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ActionController extends CommonFormController
{

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
            $pointAction = $this->request->request->get('pointaction');
            $actionType = $pointAction['type'];
        } else {
            $actionType = $this->request->query->get('type');
            $pointAction = array('type' => $actionType);
        }

        //ajax only for form fields
        if (!$actionType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('point:points:editown', 'point:points:editother', 'point:points:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the builder event
        $customComponents = $this->factory->getModel('point')->getCustomComponents();
        $form = $this->get('form.factory')->create('pointaction', $pointAction, array(
            'action'    => $this->generateUrl('mautic_pointaction_action', array('objectAction' => 'new')),
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
                    $actions          = $session->get('mautic.pointactions.add');
                    $formData         = $form->getData();
                    $pointAction       = array_merge($pointAction, $formData);
                    $pointAction['id'] = $keyId;
                    if (empty($pointAction['name'])) {
                        //set it to the event default
                        $pointAction['name'] = $this->get('translator')->trans($pointAction['settings']['label']);
                    }
                    $actions[$keyId]  = $pointAction;
                    $session->set('mautic.pointactions.add', $actions);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $actionType);
        if ($cancelled || $valid) {
            $tmpl                   = 'components';
            $viewParams['actions']  = $customComponents['actions'];
            $viewParams['expanded'] = 'actions';

        } else {
            $tmpl     = 'action';
            $formView = $form->createView();
            $this->get('templating')->getEngine('MauticPointBundle:Point:index.html.php')->get('form')
                ->setTheme($formView, 'MauticPointBundle:PointComponent');
            $viewParams['form'] = $formView;
            $header = $pointAction['settings']['label'];
            $viewParams['fieldHeader'] = $this->get('translator')->trans($header);
        }
        $viewParams['tmpl'] = $tmpl;

        $passthroughVars = array(
            'mauticContent' => 'pointaction',
            'success'       => $success,
            'target'        => '.bundle-side-inner-wrapper',
            'route'         => false
        );

        if (!empty($keyId) ) {
            //prevent undefined errors
            $entity    = new Action();
            $blank     = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $template = (!empty($pointAction['settings']['template'])) ? $pointAction['settings']['template'] :
                'MauticPointBundle:Action:generic.html.php';
            $passthroughVars['actionId']   = $keyId;
            $passthroughVars['actionHtml'] = $this->renderView($template, array(
                'inForm' => true,
                'action' => $pointAction,
                'id'     => $keyId
            ));
        }

        return $this->ajaxAction(array(
            'contentTemplate' => 'MauticPointBundle:Builder:' . $tmpl . '.html.php',
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
        $actions    = $session->get('mautic.pointactions.add', array());
        $success    = 0;
        $valid      = $cancelled = false;
        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($pointAction !== null) {
            $actionType  = $pointAction['type'];

            //ajax only for form fields
            if (!$actionType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array('point:points:editown', 'point:points:editother', 'point:points:create'), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            $form = $this->get('form.factory')->create('pointaction', $pointAction, array(
                'action'   => $this->generateUrl('mautic_pointaction_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
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
                        $actions           = $session->get('mautic.pointactions.add');
                        $formData          = $form->getData();
                        //overwrite with updated data
                        $pointAction        = array_merge($actions[$objectId], $formData);
                        if (empty($pointAction['name'])) {
                            //set it to the event default
                            $pointAction['name'] = $this->get('translator')->trans($pointAction['settings']['label']);
                        }
                        $actions[$objectId] = $pointAction;
                        $session->set('mautic.pointactions.add', $actions);

                        //generate HTML for the field
                        $keyId = $objectId;
                    }
                }
            }

            $viewParams = array('type' => $actionType);
            if ($cancelled || $valid) {
                $tmpl = 'components';

                //fire the form builder event
                $customComponents = $this->factory->getModel('point')->getCustomComponents();
                $viewParams['actions']  = $customComponents['actions'];
                $viewParams['expanded'] = 'actions';
            } else {
                $tmpl     = 'action';
                $formView = $form->createView();
                $this->get('templating')->getEngine('MauticPointBundle:Point:index.html.php')->get('form')
                    ->setTheme($formView, 'MauticPointBundle:PointComponent');
                $viewParams['form']        = $formView;
                $viewParams['fieldHeader'] = $this->get('translator')->trans($pointAction['settings']['label']);
            }
            $viewParams['tmpl'] = $tmpl;

            $passthroughVars = array(
                'mauticContent' => 'pointaction',
                'success'       => $success,
                'target'        => '.bundle-side-inner-wrapper',
                'route'         => false
            );

            if (!empty($keyId)) {
                $passthroughVars['actionId'] = $keyId;

                //prevent undefined errors
                $entity     = new Action();
                $blank      = $entity->convertToArray();
                $pointAction = array_merge($blank, $pointAction);
                $template   = (!empty($pointAction['settings']['template'])) ? $pointAction['settings']['template'] :
                    'MauticPointBundle:Action:generic.html.php';
                $passthroughVars['actionHtml'] = $this->renderView($template, array(
                    'inForm' => true,
                    'action'  => $pointAction,
                    'id'     => $keyId
                ));
            }

            return $this->ajaxAction(array(
                'contentTemplate' => 'MauticPointBundle:Builder:' . $tmpl . '.html.php',
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
        $actions   = $session->get('mautic.pointactions.add', array());
        $delete    = $session->get('mautic.pointactions.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('point:points:editown', 'point:points:editother', 'point:points:create'), 'MATCH_ONE')
        ){
            return $this->accessDenied();
        }

        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $pointAction !== null) {
            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.pointactions.remove', $delete);
            }

            $template = (!empty($pointAction['settings']['template'])) ? $pointAction['settings']['template'] :
                'MauticPointBundle:Action:generic.html.php';

            //prevent undefined errors
            $entity    = new Action();
            $blank     = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $dataArray  = array(
                'mauticContent'  => 'pointaction',
                'success'        => 1,
                'target'         => '#mauticform_'.$objectId,
                'route'          => false,
                'actionId'        => $objectId,
                'replaceContent' => true,
                'actionHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'action'  => $pointAction,
                    'id'      => $objectId,
                    'deleted' => true
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
        $actions   = $session->get('mautic.pointactions.add', array());
        $delete    = $session->get('mautic.pointactions.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('point:points:editown', 'point:points:editother', 'point:points:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $pointAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $pointAction !== null) {

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.pointactions.remove', $delete);
            }

            $template = (!empty($pointAction['settings']['template'])) ? $pointAction['settings']['template'] :
                'MauticPointBundle:Action:generic.html.php';

            //prevent undefined errors
            $entity      = new Action();
            $blank       = $entity->convertToArray();
            $pointAction = array_merge($blank, $pointAction);

            $dataArray  = array(
                'mauticContent'  => 'pointaction',
                'success'        => 1,
                'target'         => '#point_'.$objectId,
                'route'          => false,
                'actionId'        => $objectId,
                'replaceContent' => true,
                'actionHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'action'  => $pointAction,
                    'id'      => $objectId,
                    'deleted' => false
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