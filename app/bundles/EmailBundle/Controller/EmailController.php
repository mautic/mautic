<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends FormController
{

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ($page = 1)
    {
        $model = $this->factory->getModel('email');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'email:emails:viewown',
            'email:emails:viewother',
            'email:emails:create',
            'email:emails:editown',
            'email:emails:editother',
            'email:emails:deleteown',
            'email:emails:deleteother',
            'email:emails:publishown',
            'email:emails:publishother'
        ), "RETURN_ARRAY");

        if (!$permissions['email:emails:viewown'] && !$permissions['email:emails:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.email.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search  = $this->request->get('search', $this->factory->getSession()->get('mautic.email.filter', ''));
        $filters = $this->request->get('emailFilters', array());
        $this->factory->getSession()->set('mautic.email.filter', $search);

        $filter = array('string' => $search, 'force' => array(
            array('column' => 'e.variantParent', 'expr' => 'isNull')
        ));

        if ($filters) {
            foreach ($filters as $clmn => $fltr) {
                $fltrClmn = ($clmn == 'lists') ? 'l.id' : 'e.' . $clmn;

                if (is_array($fltr)) {
                    $filter['force'][] = array('column' => $fltrClmn, 'expr' => 'in', 'value' => $fltr);
                } else {
                    $filter['force'][] = array('column' => $fltrClmn, 'expr' => 'eq', 'value' => $fltr);
                }
            }
        }

        if (!$permissions['email:emails:viewother']) {
            $filter['force'][] =
                array('column' => 'e.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $orderBy    = $this->factory->getSession()->get('mautic.email.orderby', 'e.subject');
        $orderByDir = $this->factory->getSession()->get('mautic.email.orderbydir', 'DESC');

        $emails = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($emails);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.email.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticEmailBundle:Email:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.email.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->factory->getModel('category')->getLookupResults('email', '', 0);

        //retrieve a list of Lead Lists
        $lists = $this->factory->getModel('lead.list')->getUserLists();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $emails,
                'totalItems'  => $count,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity(),
                'filters'     => array(
                    array(
                        'column' => 'category',
                        'name'   => 'mautic.email.filter.categories',
                        'items'  => $categories
                    ), array(
                        'column' => 'lists',
                        'name'   => 'mautic.email.filter.lists',
                        'items'  => $lists
                    )
                )
            ),
            'contentTemplate' => 'MauticEmailBundle:Email:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email',
                'route'         => $this->generateUrl('mautic_email_index', array('page' => $page))
            )
        ));
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->factory->getModel('email');
        $security = $this->factory->getSecurity();
        $email    = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.email.page', 1);

        if ($email === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticEmailBundle:Email:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.email.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'email:emails:viewown', 'email:emails:viewother', $email->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }


        //get A/B test information
        list($parent, $children) = $model->getVariants($email);
        $properties   = array();
        $variantError = false;
        $weight       = 0;
        if (count($children)) {
            foreach ($children as $c) {
                $variantSettings = $c->getVariantSettings();

                if (is_array($variantSettings) && isset($variantSettings['winnerCriteria'])) {
                    if ($c->isPublished()) {
                        if (!isset($lastCriteria)) {
                            $lastCriteria = $variantSettings['winnerCriteria'];
                        }

                        //make sure all the variants are configured with the same criteria
                        if ($lastCriteria != $variantSettings['winnerCriteria']) {
                            $variantError = true;
                        }

                        $weight += $variantSettings['weight'];
                    }
                } else {
                    $variantSettings['winnerCriteria'] = '';
                    $variantSettings['weight']         = 0;
                }

                $properties[$c->getId()] = $variantSettings;
            }

            $properties[$parent->getId()]['weight']         = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        $abTestResults = array();
        $criteria      = $model->getBuilderComponents($email, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = array(
                    'factory'    => $this->factory,
                    'email'      => $email,
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties
                );

                //execute the callback
                if (is_callable($testSettings['callback'])) {
                    if (is_array($testSettings['callback'])) {
                        $reflection = new \ReflectionMethod($testSettings['callback'][0], $testSettings['callback'][1]);
                    } elseif (strpos($testSettings['callback'], '::') !== false) {
                        $parts      = explode('::', $testSettings['callback']);
                        $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                    } else {
                        $reflection = new \ReflectionMethod(null, $testSettings['callback']);
                    }

                    $pass = array();
                    foreach ($reflection->getParameters() as $param) {
                        if (isset($args[$param->getName()])) {
                            $pass[] = $args[$param->getName()];
                        } else {
                            $pass[] = null;
                        }
                    }
                    $abTestResults = $reflection->invokeArgs($this, $pass);
                }
            }
        }

        //get a list of recipients per list
        $stats = $model->getEmailListStats($email);

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('email', $email->getId());

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_email_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $email->getId())
            ),
            'viewParameters'  => array(
                'email'         => $email,
                'stats'         => $stats,
                'logs'          => $logs,
                'variants'      => array(
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties,
                    'criteria'   => $criteria['criteria']
                ),
                'permissions'   => $security->isGranted(array(
                    'email:emails:viewown',
                    'email:emails:viewother',
                    'email:emails:create',
                    'email:emails:editown',
                    'email:emails:editother',
                    'email:emails:deleteown',
                    'email:emails:deleteother',
                    'email:emails:publishown',
                    'email:emails:publishother'
                ), "RETURN_ARRAY"),
                'abTestResults' => $abTestResults,
                'security'      => $security,
                'previewUrl'    => $this->generateUrl('mautic_email_action', array(
                    'objectAction' => 'preview',
                    'objectId'     => $email->getId()
                ), true)
            ),
            'contentTemplate' => 'MauticEmailBundle:Email:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email'
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $model   = $this->factory->getModel('email');
        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->factory->getSession();
        if (!$this->factory->getSecurity()->isGranted('email:emails:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.email.page', 1);
        $action = $this->generateUrl('mautic_email_action', array('objectAction' => 'new'));

        $updateSelect = ($method == 'POST') ? $this->request->request->get('emailform[updateSelect]', false, true) : $this->request->get('updateSelect', false);

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array('update_select' => $updateSelect));

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $session     = $this->factory->getSession();
                    $contentName = 'mautic.emailbuilder.' . $entity->getSessionId() . '.content';
                    $content     = $session->get($contentName, array());
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    //clear the session
                    $session->remove($contentName);

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $entity->getSubject(),
                        '%menu_link%' => 'mautic_email_index',
                        '%url%'       => $this->generateUrl('mautic_email_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        );
                        $returnUrl      = $this->generateUrl('mautic_email_action', $viewParameters);
                        $template       = 'MauticEmailBundle:Email:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_email_index', $viewParameters);
                $template       = 'MauticEmailBundle:Email:index';
                //clear any modified content
                $session->remove('mautic.emailbuilder.' . $entity->getSessionId() . '.content');
            }

            $passthrough = array(
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email'
            );

            // Check to see if this is a popup
            if(isset($form['updateSelect'])) {
                $passthrough = array_merge($passthrough, array(
                    'updateSelect'  => $form['updateSelect']->getData(),
                    'emailId'       => $entity->getId(),
                    'emailSubject'  => $entity->getSubject(),
                    'emailLang'     => $entity->getLanguage()
                ));
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => $passthrough
                ));
            }
        }

        $builderComponents = $model->getBuilderComponents($entity);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'   => $this->setFormTheme($form, 'MauticEmailBundle:Email:form.html.php', 'MauticEmailBundle:FormTheme\Email'),
                'tokens' => $builderComponents['tokens'],
                'email'  => $entity
            ),
            'contentTemplate' => 'MauticEmailBundle:Email:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email',
                'updateSelect'  => InputHelper::clean($this->request->query->get('updateSelect')),
                'route'         => $this->generateUrl('mautic_email_action', array(
                    'objectAction' => 'new'
                ))
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model   = $this->factory->getModel('email');
        $entity  = $model->getEntity($objectId);
        $session = $this->factory->getSession();
        $page    = $this->factory->getSession()->get('mautic.email.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticEmailBundle:Email:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.email.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'email');
        }

        //Create the form
        $action = $this->generateUrl('mautic_email_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $contentName     = 'mautic.emailbuilder.' . $entity->getSessionId() . '.content';
                    $existingContent = $entity->getContent();
                    $newContent      = $session->get($contentName, array());
                    $content         = array_merge($existingContent, $newContent);
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    //clear the session
                    $session->remove($contentName);

                    $this->addFlash('mautic.core.notice.updated', array(
                        '%name%'      => $entity->getSubject(),
                        '%menu_link%' => 'mautic_email_index',
                        '%url%'       => $this->generateUrl('mautic_email_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ), 'warning');
                }
            } else {
                //clear any modified content
                $session->remove('mautic.emailbuilder.' . $objectId . '.content');
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                );

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_email_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'MauticEmailBundle:Email:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $builderComponents = $model->getBuilderComponents($entity);

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'   => $this->setFormTheme($form, 'MauticEmailBundle:Email:form.html.php', 'MauticEmailBundle:FormTheme\Email'),
                'tokens' => $builderComponents['tokens'],
                'email'  => $entity
            ),
            'contentTemplate' => 'MauticEmailBundle:Email:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email',
                'route'         => $this->generateUrl('mautic_email_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * Clone an entity
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        $model  = $this->factory->getModel('email');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('email:emails:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            /** @var \Mautic\EmailBundle\Entity\Email $clone */
            $clone = clone $entity;
            $clone->setSentCount(0);
            $clone->setReadCount(0);
            $clone->setRevision(0);
            $clone->setVariantSentCount(0);
            $clone->setVariantStartDate(null);
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction ($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.email.page', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticEmailBundle:Email:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('email');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.email.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'email:emails:deleteown',
                'email:emails:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'email');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $entity->getSubject(),
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * Activate the builder
     *
     * @param $objectId
     */
    public function builderAction ($objectId)
    {
        $model = $this->factory->getModel('email');

        //permission check
        if (strpos($objectId, 'new') !== false) {
            $isNew = true;
            if (!$this->factory->getSecurity()->isGranted('email:emails:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew  = false;
            $entity = $model->getEntity($objectId);
            if (!$this->factory->getSecurity()->hasEntityAccess(
                'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($this->request->query->get('template'));
        $slots    = $this->factory->getTheme($template)->getSlots('email');

        //merge any existing changes
        $newContent = $this->factory->getSession()->get('mautic.emailbuilder.' . $objectId . '.content', array());
        $content    = $entity->getContent();

        if (is_array($newContent)) {
            $content = array_merge($content, $newContent);
        }

        return $this->render('MauticEmailBundle::builder.html.php', array(
            'isNew'    => $isNew,
            'slots'    => $slots,
            'content'  => $content,
            'email'    => $entity,
            'template' => $template,
            'basePath' => $this->request->getBasePath()
        ));
    }

    /**
     * Create an AB test
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function abtestAction ($objectId)
    {
        $model  = $this->factory->getModel('email');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            $parent = $entity->getVariantParent();

            if ($parent || !$this->factory->getSecurity()->isGranted('email:emails:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;

            //reset
            $clone->setSentCount(0);
            $clone->setRevision(0);
            $clone->setVariantSentCount(0);
            $clone->setVariantStartDate(null);
            $clone->setIsPublished(false);
            $clone->setVariantParent($entity);

            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Make the variant the main
     *
     * @param $objectId
     */
    public function winnerAction ($objectId)
    {
        //todo - add confirmation to button click
        $page      = $this->factory->getSession()->get('mautic.email', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticEmailBundle:Page:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'page'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('email');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.email.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'email:emails:editown',
                'email:emails:editother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'email');
            }

            $model->convertVariant($entity);

            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.email.notice.activated',
                'msgVars' => array(
                    '%name%' => $entity->getSubject(),
                    '%id%'   => $objectId
                )
            );

            $postActionVars['viewParameters']  = array(
                'objectAction' => 'view',
                'objectId'     => $objectId
            );
            $postActionVars['returnUrl']       = $this->generateUrl('mautic_page_action', $postActionVars['viewParameters']);
            $postActionVars['contentTemplate'] = 'MauticEmailBundle:Page:view';

        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * Manually sends emails
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendAction ($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model   = $this->factory->getModel('email');
        $entity  = $model->getEntity($objectId);
        $session = $this->factory->getSession();
        $page    = $session->get('mautic.email.page', 1);
        $flashes = array();

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticEmailBundle:Email:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_email_index',
                'mauticContent' => 'email'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.email.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess('email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        //make sure email and category are published
        $category     = $entity->getCategory();
        $catPublished = (!empty($category)) ? $category->isPublished() : true;
        $published    = $entity->isPublished();

        if ($catPublished && $published && $this->request->getMethod() == 'POST') {
            //process and send
            $model->sendEmailToLists($entity);
            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.email.notice.send.success',
                'msgVars' => array('%subject%' => $entity->getSubject())
            );
        } else {
            $flashes[] = array(
                'type'    => 'error',
                'msg'     => 'mautic.email.error.send',
                'msgVars' => array('%subject%' => $entity->getSubject())
            );
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }


    /**
     * Send example email to current user
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function exampleAction ($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model   = $this->factory->getModel('email');
        $entity  = $model->getEntity($objectId);

        //not found or not allowed
        if ($entity === null || (!$this->factory->getSecurity()->hasEntityAccess('email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()))) {
            return $this->viewAction($objectId);
        }

        // Grab a random lead
        $lead = $this->factory->getModel('lead')->getRepository()->getRandomLead();

        // Send to current user
        $model->sendEmailToUser($entity, $this->factory->getUser()->getId(), $lead);

        $this->addFlash('mautic.email.notice.test_sent.success');

        return $this->viewAction($objectId);
    }

    /**
     * Preview email
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction ($objectId)
    {
        $model  = $this->factory->getModel('email');
        $entity = $model->getEntity($objectId);

        if (!$this->factory->getSecurity()->hasEntityAccess('email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        //bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        if ($entity->getContentMode() == 'builder') {
            $template = $entity->getTemplate();
            $slots    = $this->factory->getTheme($template)->getSlots('email');

            $response = $this->render('MauticEmailBundle::public.html.php', array(
                'inBrowser' => true,
                'slots'     => $slots,
                'content'   => $entity->getContent(),
                'email'     => $entity,
                'lead'      => null,
                'template'  => $template,
                'idHash'    => $idHash
            ));

            //replace tokens
            $content = $response->getContent();
        } else {
            $content = $entity->getCustomHtml();
        }

        $dispatcher = $this->get('event_dispatcher');
        if ($dispatcher->hasListeners(EmailEvents::EMAIL_ON_DISPLAY)) {
            $event = new EmailSendEvent($content, $entity, null, $idHash);
            $dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $event);
            $content = $event->getContent();
        }

        return new Response($content);
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction ()
    {
        $page      = $this->factory->getSession()->get('mautic.email.page', 1);
        $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticEmailBundle:Email:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_email_index',
                'mauticContent' => 'email'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('email');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.email.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                    'email:emails:viewown', 'email:emails:viewother', $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'email', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.email.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }
}
