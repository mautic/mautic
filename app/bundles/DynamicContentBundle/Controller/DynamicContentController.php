<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\HttpFoundation\JsonResponse;

class DynamicContentController extends FormController
{
    /**
     * @return array
     */
    protected function getPermissions()
    {
        return (array) $this->get('mautic.security')->isGranted([
            'dynamicContent:dynamicContents:viewown',
            'dynamicContent:dynamicContents:viewother',
            'dynamicContent:dynamicContents:create',
            'dynamicContent:dynamicContents:editown',
            'dynamicContent:dynamicContents:editother',
            'dynamicContent:dynamicContents:deleteown',
            'dynamicContent:dynamicContents:deleteother',
            'dynamicContent:dynamicContents:publishown',
            'dynamicContent:dynamicContents:publishother'
        ], "RETURN_ARRAY");
    }

    /**
     * {@inheritdoc}
     */
    public function indexAction($page = 1)
    {
        $model = $this->getModel('dynamicContent');

        $permissions = $this->getPermissions();

        if (! $permissions['dynamicContent:dynamicContents:viewown'] && ! $permissions['dynamicContent:dynamicContents:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.dynamicContent.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        // fetch

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.dynamicContent.filter', ''));
        $this->get('session')->set('mautic.dynamicContent.filter', $search);
        //do not list variants in the main list
        $filter['force'][] = ['column' => 'e.variantParent', 'expr' => 'isNull'];

        $orderBy    = $this->factory->getSession()->get('mautic.dynamicContent.orderby', 'e.name');
        $orderByDir = $this->factory->getSession()->get('mautic.dynamicContent.orderbydir', 'DESC');

        $entities = $model->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir
        ]);

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.dynamicContent.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->getModel('page')->getLookupResults('category', '', 0);

        return $this->delegateView([
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:list.html.php',
            'passthroughVars' => [
                'activeLink'     => '#mautic_dwc_index',
                'mauticContent'  => 'dwc',
                'route'          => $this->generateUrl('mautic_dwc_index', ['page' => $page])
            ],
            'viewParameters'  => [
                'searchValue' => $search,
                'items'       => $entities,
                'categories'  => $categories,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function newAction($entity = null)
    {
        if (! $this->accessGranted('dynamicContent:dynamicContents:viewown')) {
            return $this->accessDenied();
        }

        if (! $entity instanceof DynamicContent) {
            $entity = new DynamicContent;
        }

        /** @var \Mautic\DynamicContentBundle\Model\DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $page   = $this->factory->getSession()->get('mautic.dynamicContent.page', 1);
        $retUrl = $this->generateUrl('mautic_dwc_index', ['page' => $page]);
        $action = $this->generateUrl('mautic_dwc_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        if ($this->request->getMethod() === 'POST') {
            $valid = false;

            if (! $cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_dwc_index',
                        '%url%'       => $this->generateUrl('mautic_dwc_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ])
                    ]);
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $retUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
                    'passthroughVars' => [
                        'activeLink'    => '#mautic_dwc_index',
                        'mauticContent' => 'dwc'
                    ]
                ]);
            } elseif ($valid && ! $cancelled) {
                return $this->editAction($entity->getId(), true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php')
            ],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dwc_index',
                'route'         => $action,
                'mauticContent' => 'dwc'
            ]
        ]);
    }

    /**
     * Generate's edit form and processes post data
     *
     * @param            $objectId
     * @param bool|false $ignorePost
     *
     * @return array | JsonResponse | RedirectResponse | Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        /** @var DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);
        $page   = $this->factory->getSession()->get('mautic.dynamicContent.page', 1);
        $retUrl = $this->generateUrl('mautic_dwc_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $retUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dwc_index',
                'mauticContent' => 'dwc'
            ]
        ];

        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type' => 'error',
                            'msg'  => 'mautic.dwc.error.notfound',
                            'msgVars' => ['%id%' => $objectId]
                        ]
                    ]
                ])
            );
        } elseif (! $this->factory->getSecurity()->hasEntityAccess(true, 'dynamicContent:dynamicContents:editother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'dynamicContent');
        }

        $action = $this->generateUrl('mautic_dwc_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (! $ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;

            if (! $cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $entity->getName(),
                        '%menu_link%' => 'mautic_dwc_index',
                        '%url%'       => $this->generateUrl('mautic_dwc_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ])
                    ]);
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect($postActionVars);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView([
            'viewParameters'  => [
                'form'            => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php'),
                'currentListId'   => $objectId,
            ],
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dwc_index',
                'route'         => $action,
                'mauticContent' => 'dwc'
            ]
        ]);
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\DynamicContentBundle\Model\DynamicContentModel $model */
        $model    = $this->getModel('dynamicContent');
        $security = $this->get('mautic.security');
        $entity   = $model->getEntity($objectId);
        
        //set the page we came from
        $page = $this->get('session')->get('mautic.dynamicContent.page', 1);

        if ($entity === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_dwc_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPageBundle:Page:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_dwc_index',
                    'mauticContent' => 'dwc'
                ],
                'flashes'         => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.dynamicContent.error.notfound',
                        'msgVars' => ['%id%' => $objectId]
                    ]
                ]
            ]);
        } elseif (!$security->hasEntityAccess('dynamicContent:dynamicContents:viewown', 'dynamicContent:dynamicContents:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        /** @var DynamicContent $parent */
        /** @var DynamicContent[] $children */
        list($parent, $children) = $model->getVariants($entity);

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('dynamicContent', $entity->getId(), $entity->getDateAdded());

        return $this->delegateView([
            'returnUrl'       => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                ]),
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:details.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dwc_index',
                'mauticContent' => 'dwc'
            ],
            'viewParameters'  => [
                'entity'      => $entity,
                'permissions' => $this->getPermissions(),
                'security'    => $security,
                'logs'        => $logs,
                'variants'    => [
                    'parent'   => $parent,
                    'children' => $children
                ]
            ]
        ]);
    }

    /**
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addvariantAction($objectId)
    {
        /** @var \Mautic\DynamicContentBundle\Model\DynamicContentModel $model */
        $model  = $this->getModel('dynamicContent');
        $entity = $model->getEntity($objectId);

        if ($entity !== null) {
            $parent = $entity->getVariantParent();

            if ($parent || !$this->get('mautic.security')->isGranted('dynamicContent:dynamicContents:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'dynamicContent:dynamicContents:viewown', 'dynamicContent:dynamicContents:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            /** @var DynamicContent $clone */
            $clone = clone $entity;

            $variantCount = count($entity->getVariantChildren());

            //reset
            // Auto update the name
            $name = $clone->getName();
            $clone->setName('Variant ' . ($variantCount + 1) . ': ' . $name);

            $clone->setVariantParent($entity);
        }

        return $this->newAction($clone);
    }
}