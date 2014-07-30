<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

/**
 * Class FormController
 *
 * @package Mautic\CoreBundle\Controller
 */
class FormController extends CommonController
{

    /**
     * Checks to see if the form was cancelled
     *
     * @param Form $form
     * @return int
     */
    protected function isFormCancelled(Form &$form)
    {
        $name   = $form->getName();
        $cancel = $this->request->request->get($name . '[buttons][cancel]', false, true);
        return ($cancel !== false);
    }

    /**
     * Binds form data, checks validity, and determines cancel request
     *
     * @param Form    $form
     * @return int
     */
    protected function isFormValid(Form &$form)
    {
        //bind request to the form
        $form->handleRequest($this->request);

        return $form->isValid();
    }

    /**
     * Returns view to index with a locked out message
     *
     * @param        $returnUrl
     * @param        $entity
     * @param        $entityType
     */
    protected function isLocked($postActionVars, $entity, $entityType)
    {
        $date      = $entity->getCheckedOut();
        $returnUrl = !empty($postActionVars['returnUrl']) ?
            urlencode($postActionVars['returnUrl']) :
            urlencode($this->generateUrl('mautic_core_index'));
        $override  = '';

        $factory      = $this->get('mautic.factory');
        $model        = $factory->getModel($entityType);
        $nameFunction = $model->getNameGetter();
        if ($this->get('mautic.factory')->getUser()->isAdmin()) {
            $override = $this->get('translator')->trans('mautic.core.override.lock',array(
                '%url%' => $this->generateUrl('mautic_core_form_action', array(
                        'objectAction' => 'unlock',
                        'objectModel'  => $entityType,
                        'objectId'     => $entity->getId(),
                        'returnUrl'    => $returnUrl,
                        'name'         => urlencode($entity->$nameFunction())
                    )
                )
            ));
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => array(array(
                    'type' => 'error',
                    'msg'  => 'mautic.core.error.locked',
                    'msgVars' => array(
                        "%name%"        => $entity->$nameFunction(),
                        "%user%"        => $entity->getCheckedOutBy()->getName(),
                        '%contactUrl%'  => $this->generateUrl('mautic_user_action',
                            array(
                                'objectAction' => 'contact',
                                'objectId'     => $entity->getCheckedOutBy()->getId(),
                                'entity'    => $entityType,
                                'id'        => $entity->getId(),
                                'subject'   => 'locked',
                                'returnUrl' => $returnUrl
                            )
                        ),
                        '%date%'        => $date->format($factory->getParameter('date_format_dateonly')),
                        '%time%'        => $date->format($factory->getParameter('date_format_timeonly')),
                        '%datetime%'    => $date->format($factory->getParameter('date_format_full')),
                        '%override%'    => $override
                    )
                ))
            ))
        );
    }

    /**
     *
     */
    public function unlockAction($id, $model)
    {
        if ($this->get('mautic.security')->isAdmin()) {
            $bundle = $object = $model;
            if (strpos($model, ':')) {
                list($bundle, $object) = explode(':', $model);
            }
            $model = $this->get('mautic.factory')->getModel($object);

            $entity = $model->getEntity($id);
            if ($entity !== null) {
                if ($entity->getCheckedOutBy() !== null) {
                    $serializer = $this->get('jms_serializer');
                    $details    = $serializer->serialize(array(
                        "checkedOut"   => array(
                            $entity->getCheckedOut(),
                            ""
                        ),
                        "checkedOutBy" => array(
                            $entity->getCheckedOutBy()->getId(),
                            ""
                        )
                    ), 'json');

                    $log = array(
                        "bundle"    => $bundle,
                        "object"    => $object,
                        "objectId"  => $id,
                        "action"    => "update",
                        "details"   => $details,
                        "ipAddress" => $this->request->server->get('REMOTE_ADDR')
                    );
                    $this->get('mautic.factory')->getModel('core.auditLog')->writeToLog($log);

                    $model->unlockEntity($entity);
                }
            }
            $returnUrl = urldecode($this->request->get('returnUrl'));
            if (empty($returnUrl)) {
                $returnUrl = $this->generateUrl('mautic_core_index');
            }
            $this->get('session')->getFlashBag()->add(
                'notice',
                $this->get('translator')->trans('mautic.core.action.entity.unlocked',
                    array('%name%' => urldecode($this->request->get('name'))),
                    'flashes'
                )
            );
            return $this->redirect($returnUrl);
        } else {
            $this->accessDenied();
        }
    }

    /**
     * Sets a specific them for the form
     *
     * @param $form
     * @param $template
     * @param $theme
     * @return mixed
     */
    protected function setFormTheme(Form $form, $template, $theme)
    {
        $formView = $form->createView();
        $this->container->get('templating')->getEngine($template)->get('form')
            ->setTheme($formView, $theme);
        return $formView;
    }
}