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

class DynamicContentController extends FormController
{
    /**
     * @return array
     */
    protected function getPermissions()
    {
        return (array) $this->factory->getSecurity()->isGranted([
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
        $this->factory->getSession()->set('mautic.dynamicContent.filter', $search);

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.dynamicContent.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->getModel('page')->getLookupResults('category', '', 0);

        return $this->delegateView([
            'contentTemplate' => 'MauticDynamicContentBundle:DynamicContent:index.html.php',
            'passthroughVars' => [
                'activeLink'     => '#mautic_dwc_index',
                'mauticContent'  => 'dwc',
                'route'          => $this->generateUrl('mautic_dwc_index', ['page' => $page])
            ],
            'viewParameters'  => [
                'searchValue' => $search,
                'items'       => [],
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

    public function newAction()
    {
        if (! $this->accessGranted('dynamicContent:dynamicContents:viewown')) {
            return $this->accessDenied();
        }

        $entity = new DynamicContent;
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
                'form' => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php', 'MauticLeadBundle:FormTheme\Filter')
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
                        '%menu_link%' => 'mautic_segment_index',
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
                'form'            => $this->setFormTheme($form, 'MauticDynamicContentBundle:DynamicContent:form.html.php', 'MauticLeadBundle:FormTheme\Filter'),
                'currentListId'   => $objectId,
            ],
            'contentTemplate' => 'MauticLeadBundle:List:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_dwc_index',
                'route'         => $action,
                'mauticContent' => 'dwc'
            ]
        ]);
    }
}