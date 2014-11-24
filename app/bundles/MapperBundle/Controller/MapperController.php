<?php
/**
* @package     Mautic
* @copyright   2014 Mautic, NP. All rights reserved.
* @author      Mautic
* @link        http://mautic.com
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\MapperBundle\Helper\IntegrationHelper;

class MapperController extends FormController
{
    /**
     * @param        $bundle
     * @param        $objectAction
     * @param int    $objectId
     * @param string $objectModel
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeMapperAction($application, $client, $object, $objectAction = '') {
        if (method_exists($this, "{$objectAction}MapperAction")) {
            return $this->{"{$objectAction}MapperAction"}($application, $client, $object);
        } else {
            return $this->accessDenied();
        }
    }

    public function indexAction($application, $client)
    {
        if (!$this->factory->getSecurity()->isGranted($application.':mapper:create')) {
            return $this->accessDenied();
        }

        $entities = IntegrationHelper::getMappers($this->factory, $application);

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            $application.':mapper:view',
            $application.':mapper:create',
            $application.':mapper:edit',
            $application.':mapper:delete'
        ), "RETURN_ARRAY");

        $viewParams = array(
            'client'   => $client,
            'application' => $application
        );

        $tmpl = $this->request->get('tmpl', 'index');

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_mapper_client_objects_index', $viewParams),
            'viewParameters'  => array(
                'application' => $application,
                'client'      => $client,
                'items'       => $entities,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticMapperBundle:Mapper:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_'.$application.'client_'.$client.'objects_index',
                'mauticContent'  => 'clients',
                'route'          => $this->generateUrl('mautic_mapper_client_objects_index', $viewParams)
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editMapperAction ($application, $client, $object)
    {
        $ignorePost     = false;
        $session        = $this->factory->getSession();
        $modelClient    = $this->factory->getModel('mapper.ApplicationClient');
        $modelMapper    = $this->factory->getModel('mapper.ApplicationObjectMapper');

        //verify client
        $entityClient = $modelClient->loadByAlias($client);

        if (!$entityClient->getId()) {
            return $this->accessDenied();
        }

        $entity     = $modelMapper->getEntity($object, $entityClient->getId());

        //set the page we came from
        $viewParams = array(
            'client'   => $client,
            'application' => $application
        );
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_mapper_client_objects_index', $viewParams);

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticMapperBundle:Mapper:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$application.'client_index',
                'mauticContent' => 'client'
            )
        );

        //not found
        if ($entity === null) {
            $entity = $modelMapper->getEntity();
        }  elseif (!$this->factory->getSecurity()->isGranted($application.':mapper:view')) {
            return $this->accessDenied();
        } elseif ($modelMapper->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'mapper.ApplicationClient');
        }

        //Create the form
        $action = $this->generateUrl('mautic_mapper_client_object_action', array(
            'objectAction' => 'edit',
            'object'     => $object,
            'application'  => $application,
            'client'    => $client
        ));

        $entity->setObjectName($object);
        $entity->setApplicationClientId($entityClient);
        $form = $modelMapper->createForm($entity, $this->get('form.factory'), $action, array('application' => $application));

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form) ){
                        //form is valid so process the data
                    $modelMapper->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.mapper.notice.updated', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'  => $this->generateUrl('mautic_mapper_client_action', array(
                                'objectAction' => 'edit',
                                'object'       => $entity->getObjectName(),
                                'client'       => $entityClient->getAlias(),
                                'application'  => $application
                            ))
                        ), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $modelMapper->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_mapper_client_objects_index', $viewParams),
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => 'MauticMapperBundle:Mapper:index'
                    ))
                );
            }
        } else {
            //lock the entity
            $modelMapper->lockEntity($entity);
        }

        return $this->delegateView(array(
            'viewParameters' => array(
                'form'           => $form->createView(),
                'activeCategory' => $entity,
                'application'    => $application
            ),
            'contentTemplate' => sprintf('Mautic%sBundle:%s:form.html.php', ucfirst($application), $object),
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_mapper_client_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                        'application'  => $application
                    ))
            )
        ));
    }
}
