<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FormController
 */
class FormController extends CommonFormController
{

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
            array(
                'form:forms:viewown',
                'form:forms:viewother',
                'form:forms:create',
                'form:forms:editown',
                'form:forms:editother',
                'form:forms:deleteown',
                'form:forms:deleteother',
                'form:forms:publishown',
                'form:forms:publishother'

            ),
            "RETURN_ARRAY"
        );

        if (!$permissions['form:forms:viewown'] && !$permissions['form:forms:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $session = $this->get('session');

        //set limits
        $limit = $session->get('mautic.form.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.form.filter', ''));
        $session->set('mautic.form.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['form:forms:viewother']) {
            $filter['force'][] = array('column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->user->getId());
        }

        $orderBy    = $session->get('mautic.form.orderby', 'f.name');
        $orderByDir = $session->get('mautic.form.orderbydir', 'ASC');

        $forms = $this->getModel('form.form')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($forms);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : ((ceil($count / $limit)) ?: 1) ?: 1;

            $session->set('mautic.form.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $lastPage));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage),
                    'contentTemplate' => 'MauticFormBundle:Form:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_form_index',
                        'mauticContent' => 'form'
                    )
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.form.page', $page);

        $viewParameters = array(
            'searchValue' => $search,
            'items'       => $forms,
            'totalItems'  => $count,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->get('mautic.security'),
            'tmpl'        => $this->request->get('tmpl', 'index')
        );

        return $this->delegateView(
            array(
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'MauticFormBundle:Form:list.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl('mautic_form_index', array('page' => $page))
                )
            )
        );
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\FormBundle\Model\FormModel $model */
        $model      = $this->getModel('form');
        $activeForm = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->get('session')->get('mautic.form.page', 1);

        if ($activeForm === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $page));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticFormBundle:Form:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_form_index',
                        'mauticContent' => 'form'
                    ),
                    'flashes'         => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.form.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                )
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:viewown',
            'form:forms:viewother',
            $activeForm->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        $permissions = $this->get('mautic.security')->isGranted(
            array(
                'form:forms:viewown',
                'form:forms:viewother',
                'form:forms:create',
                'form:forms:editown',
                'form:forms:editother',
                'form:forms:deleteown',
                'form:forms:deleteother',
                'form:forms:publishown',
                'form:forms:publishother'

            ),
            "RETURN_ARRAY"
        );

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('form', $objectId, $activeForm->getDateAdded());

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', array());
        $action          = $this->generateUrl('mautic_form_action', array('objectAction' => 'view', 'objectId' => $objectId));
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, array('action' => $action));

        // Submission stats per time period
        $timeStats = $this->getModel('form.submission')->getSubmissionsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            array('form_id' => $objectId)
        );

        // Only show actions and fields that still exist
        $customComponents  = $model->getCustomComponents();
        $activeFormActions = array();
        foreach ($activeForm->getActions() as $formAction) {
            if (!isset($customComponents['actions'][$formAction->getType()])) {
                continue;
            }
            $type                          = explode('.', $formAction->getType());
            $activeFormActions[$type[0]][] = $formAction;
        }

        $activeFormFields = array();
        $fieldHelper      = new FormFieldHelper($this->get('translator'));
        $availableFields  = $fieldHelper->getList($customComponents['fields']);
        foreach ($activeForm->getFields() as $field) {
            if (!isset($availableFields[$field->getType()])) {
                continue;
            }

            $activeFormFields[] = $field;
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'activeForm'        => $activeForm,
                    'page'              => $page,
                    'logs'              => $logs,
                    'permissions'       => $permissions,
                    'security'          => $this->get('mautic.security'),
                    'stats'             => array(
                        'submissionsInTime' => $timeStats,
                    ),
                    'dateRangeForm'     => $dateRangeForm->createView(),
                    'activeFormActions' => $activeFormActions,
                    'activeFormFields'  => $activeFormFields,
                    'formScript'        => htmlspecialchars($model->getFormScript($activeForm), ENT_QUOTES, "UTF-8"),
                    'formContent'       => htmlspecialchars($model->getContent($activeForm, false), ENT_QUOTES, "UTF-8")
                ),
                'contentTemplate' => 'MauticFormBundle:Form:details.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $action
                )
            )
        );
    }

    /**
     * Generates new form and processes post data
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function newAction()
    {
        /** @var \Mautic\FormBundle\Model\FormModel $model */
        $model   = $this->getModel('form');
        $entity  = $model->getEntity();
        $session = $this->get('session');

        if (!$this->get('mautic.security')->isGranted('form:forms:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get('mautic.form.page', 1);

        $sessionId = $this->request->request->get('mauticform[sessionId]', 'mautic_'.sha1(uniqid(mt_rand(), true)), true);

        //set added/updated fields
        $modifiedFields = $session->get('mautic.form.'.$sessionId.'.fields.modified', array());
        $deletedFields  = $session->get('mautic.form.'.$sessionId.'.fields.deleted', array());

        //set added/updated actions
        $modifiedActions = $session->get('mautic.form.'.$sessionId.'.actions.modified', array());
        $deletedActions  = $session->get('mautic.form.'.$sessionId.'.actions.deleted', array());

        $action = $this->generateUrl('mautic_form_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //only save fields that are not to be deleted
                    $fields = array_diff_key($modifiedFields, array_flip($deletedFields));

                    //make sure that at least one field is selected
                    if (empty($fields)) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.form.form.fields.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        $model->setFields($entity, $fields);

                        try {
                            if ($entity->isStandalone()) {
                                // Set alias to prevent SQL errors
                                $alias = $model->cleanAlias($entity->getName(), '', 10);
                                $entity->setAlias($alias);

                                // Set timestamps
                                $model->setTimestamps($entity, true, false);

                                // Save the form first and new actions so that new fields are available to actions.
                                // Using the repository function to not trigger the listeners twice.
                                $model->getRepository()->saveEntity($entity);

                                // Only save actions that are not to be deleted
                                $actions = array_diff_key($modifiedActions, array_flip($deletedActions));

                                // Set and persist actions
                                $model->setActions($entity, $actions);
                            }

                            // Save and trigger listeners
                            $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                            $this->addFlash(
                                'mautic.core.notice.created',
                                array(
                                    '%name%'      => $entity->getName(),
                                    '%menu_link%' => 'mautic_form_index',
                                    '%url%'       => $this->generateUrl(
                                        'mautic_form_action',
                                        array(
                                            'objectAction' => 'edit',
                                            'objectId'     => $entity->getId()
                                        )
                                    )
                                )
                            );

                            if ($form->get('buttons')->get('save')->isClicked()) {
                                $viewParameters = array(
                                    'objectAction' => 'view',
                                    'objectId'     => $entity->getId()
                                );
                                $returnUrl      = $this->generateUrl('mautic_form_action', $viewParameters);
                                $template       = 'MauticFormBundle:Form:view';
                            } else {
                                //return edit view so that all the session stuff is loaded
                                return $this->editAction($entity->getId(), true);
                            }
                        } catch (\Exception $e) {
                            $form['name']->addError(
                                new FormError($this->get('translator')->trans('mautic.form.schema.failed', array(), 'validators'))
                            );
                            $valid = false;

                            if ('dev' == $this->container->getParameter('kernel.environment')) {
                                throw $e;
                            }
                        }
                    }
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_form_index', $viewParameters);
                $template       = 'MauticFormBundle:Form:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //clear temporary fields
                $this->clearSessionComponents($sessionId);

                return $this->postActionRedirect(
                    array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => array(
                            'activeLink'    => '#mautic_form_index',
                            'mauticContent' => 'form'
                        )
                    )
                );
            }
        } else {
            //clear out existing fields in case the form was refreshed, browser closed, etc
            $this->clearSessionComponents($sessionId);
            $modifiedFields = $modifiedActions = $deletedActions = $deletedFields = array();

            $form->get('sessionId')->setData($sessionId);

            //add a submit button
            $keyId = 'new'.hash('sha1', uniqid(mt_rand()));
            $field = new Field();

            $modifiedFields[$keyId]                    = $field->convertToArray();
            $modifiedFields[$keyId]['label']           = $this->translator->trans('mautic.core.form.submit');
            $modifiedFields[$keyId]['alias']           = 'submit';
            $modifiedFields[$keyId]['showLabel']       = 1;
            $modifiedFields[$keyId]['type']            = 'button';
            $modifiedFields[$keyId]['id']              = $keyId;
            $modifiedFields[$keyId]['inputAttributes'] = 'class="btn btn-default"';
            $modifiedFields[$keyId]['formId']          = $sessionId;
            unset($modifiedFields[$keyId]['form']);
            $session->set('mautic.form.'.$sessionId.'.fields.modified', $modifiedFields);
        }

        //fire the form builder event
        $customComponents = $model->getCustomComponents($sessionId);

        $fieldHelper = new FormFieldHelper($this->get('translator'));

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'fields'         => $fieldHelper->getList($customComponents['fields']),
                    'actions'        => $customComponents['choices'],
                    'formFields'     => $modifiedFields,
                    'formActions'    => $modifiedActions,
                    'deletedFields'  => $deletedFields,
                    'deletedActions' => $deletedActions,
                    'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'activeForm'     => $entity,
                    'form'           => $form->createView()
                ),
                'contentTemplate' => 'MauticFormBundle:Builder:index.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl(
                        'mautic_form_action',
                        array(
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     * @param bool $forceTypeSelection
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function editAction($objectId, $ignorePost = false, $forceTypeSelection = false)
    {
        /** @var \Mautic\FormBundle\Model\FormModel $model */
        $model     = $this->getModel('form');
        $formData  = $this->request->request->get('mauticform');
        $sessionId = isset($formData['sessionId']) ? $formData['sessionId'] : null;

        if ($objectId instanceof Form) {
            $entity   = $objectId;
            $objectId = 'mautic_'.sha1(uniqid(mt_rand(), true));
        } else {
            $entity = $model->getEntity($objectId);

            // Process submit of cloned form
            if ($entity == null && $objectId == $sessionId) {
                $entity = $model->getEntity();
            }
        }

        $session    = $this->get('session');
        $cleanSlate = true;

        //set the page we came from
        $page = $this->get('session')->get('mautic.form.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticFormBundle:Form:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form'
            )
        );

        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    array(
                        'flashes' => array(
                            array(
                                'type'    => 'error',
                                'msg'     => 'mautic.form.error.notfound',
                                'msgVars' => array('%id%' => $objectId)
                            )
                        )
                    )
                )
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:editown',
            'form:forms:editother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'form.form');
        }

        $action = $this->generateUrl('mautic_form_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                //set added/updated fields
                $modifiedFields = $session->get('mautic.form.'.$objectId.'.fields.modified', array());
                $deletedFields  = $session->get('mautic.form.'.$objectId.'.fields.deleted', array());
                $fields         = array_diff_key($modifiedFields, array_flip($deletedFields));

                //set added/updated actions
                $modifiedActions = $session->get('mautic.form.'.$objectId.'.actions.modified', array());
                $deletedActions  = $session->get('mautic.form.'.$objectId.'.actions.deleted', array());
                $actions         = array_diff_key($modifiedActions, array_flip($deletedActions));

                if ($valid = $this->isFormValid($form)) {
                    //make sure that at least one field is selected
                    if (empty($fields)) {
                        //set the error
                        $form->addError(
                            new FormError(
                                $this->get('translator')->trans('mautic.form.form.fields.notempty', array(), 'validators')
                            )
                        );
                        $valid = false;
                    } else {
                        $model->setFields($entity, $fields);
                        $model->deleteFields($entity, $deletedFields);

                        if ($entity->isStandalone()) {
                            if (!$alias = $entity->getAlias()) {
                                $alias = $model->cleanAlias($entity->getName(), '', 10);
                                $entity->setAlias($alias);
                            }

                            if (!$entity->getId()) {
                                // Set timestamps because this is a new clone
                                $model->setTimestamps($entity, true, false);
                            }

                            // save the form first so that new fields are available to actions
                            // use the repository method to not trigger listeners twice
                            $model->getRepository()->saveEntity($entity);

                            if (count($actions)) {
                                // Now set and persist the actions
                                $model->setActions($entity, $actions);
                            }

                            // Delete deleted actions
                            if (count($deletedActions)) {
                                $this->getModel('form.action')->deleteEntities($deletedActions);
                            }
                        } else {
                            // Clear the actions
                            $entity->clearActions();

                            // Delete all actions
                            if (count($modifiedActions)) {
                                $this->getModel('form.action')->deleteEntities(array_keys($modifiedActions));
                            }
                        }

                        // Persist and execute listeners
                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        // Reset objectId to entity ID (can be session ID in case of cloned entity)
                        $objectId = $entity->getId();

                        $this->addFlash(
                            'mautic.core.notice.updated',
                            array(
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => 'mautic_form_index',
                                '%url%'       => $this->generateUrl(
                                    'mautic_form_action',
                                    array(
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId()
                                    )
                                )
                            )
                        );

                        if ($form->get('buttons')->get('save')->isClicked()) {
                            $viewParameters = array(
                                'objectAction' => 'view',
                                'objectId'     => $entity->getId()
                            );
                            $returnUrl      = $this->generateUrl('mautic_form_action', $viewParameters);
                            $template       = 'MauticFormBundle:Form:view';
                        }
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl('mautic_form_index', $viewParameters);
                $template       = 'MauticFormBundle:Form:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                //remove fields from session
                $this->clearSessionComponents($objectId);

                // Clear session items in case columns changed
                $session->remove('mautic.formresult.'.$entity->getId().'.orderby');
                $session->remove('mautic.formresult.'.$entity->getId().'.orderbydir');
                $session->remove('mautic.formresult.'.$entity->getId().'.filters');

                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        array(
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template
                        )
                    )
                );
            } elseif ($form->get('buttons')->get('apply')->isClicked()) {
                // Rebuild everything to include new ids
                $cleanSlate = true;
                $reorder    = true;

                if ($valid) {
                    // Rebuild the form with new action so that apply doesn't keep creating a clone
                    $action = $this->generateUrl('mautic_form_action', ['objectAction' => 'edit', 'objectId' => $entity->getId()]);
                    $form   = $model->createForm($entity, $this->get('form.factory'), $action);
                }
            }
        } else {
            $cleanSlate = true;

            //lock the entity
            $model->lockEntity($entity);
        }

        if (!$form->isSubmitted()) {
            $form->get('sessionId')->setData($objectId);
        }

        // Get field and action settings
        $customComponents = $model->getCustomComponents();
        $fieldHelper      = new FormFieldHelper($this->get('translator'));
        $availableFields  = $fieldHelper->getList($customComponents['fields']);

        if ($cleanSlate) {
            //clean slate
            $this->clearSessionComponents($objectId);

            //load existing fields into session
            $modifiedFields = array();
            $usedLeadFields = array();
            $existingFields = $entity->getFields()->toArray();

            foreach ($existingFields as $formField) {
                // Check to see if the field still exists
                if ($formField->getType() !== 'button' && !isset($availableFields[$formField->getType()])) {
                    continue;
                }

                $id    = $formField->getId();
                $field = $formField->convertToArray();

                if (!$id) {
                    // Cloned entity
                    $id = $field['id'] = $field['sessionId'] = 'new' . hash('sha1', uniqid(mt_rand()));
                }

                unset($field['form']);

                if (isset($customComponents['fields'][$field['type']])) {
                    // Set the custom parameters
                    $field['customParameters'] = $customComponents['fields'][$field['type']];
                }

                $modifiedFields[$id] = $field;


                if (!empty($field['leadField'])) {
                    $usedLeadFields[$id] = $field['leadField'];
                }
            }

            $session->set('mautic.form.'.$objectId.'.fields.leadfields', $usedLeadFields);

            if (!empty($reorder)) {
                uasort(
                    $modifiedFields,
                    function ($a, $b) {
                        return $a['order'] > $b['order'];
                    }
                );
            }

            $session->set('mautic.form.'.$objectId.'.fields.modified', $modifiedFields);
            $deletedFields = array();

            // Load existing actions into session
            $modifiedActions = array();
            $existingActions = $entity->getActions()->toArray();

            foreach ($existingActions as $formAction) {
                // Check to see if the action still exists
                if (!isset($customComponents['actions'][$formAction->getType()])) {
                    continue;
                }

                $id     = $formAction->getId();
                $action = $formAction->convertToArray();

                if (!$id) {
                    // Cloned entity so use a random Id instead
                    $action['id'] = $id = 'new' . hash('sha1', uniqid(mt_rand()));
                }
                unset($action['form']);

                $modifiedActions[$id] = $action;
            }

            if (!empty($reorder)) {
                uasort(
                    $modifiedActions,
                    function ($a, $b) {
                        return $a['order'] > $b['order'];
                    }
                );
            }

            $session->set('mautic.form.'.$objectId.'.actions.modified', $modifiedActions);
            $deletedActions = array();
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'fields'             => $availableFields,
                    'actions'            => $customComponents['choices'],
                    'formFields'         => $modifiedFields,
                    'formActions'        => $modifiedActions,
                    'deletedFields'      => $deletedFields,
                    'deletedActions'     => $deletedActions,
                    'tmpl'               => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'activeForm'         => $entity,
                    'form'               => $form->createView(),
                    'forceTypeSelection' => $forceTypeSelection
                ),
                'contentTemplate' => 'MauticFormBundle:Builder:index.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_form_index',
                    'mauticContent' => 'form',
                    'route'         => $this->generateUrl(
                        'mautic_form_action',
                        array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model = $this->getModel('form.form');

        /** @var \Mautic\FormBundle\Entity\Form $entity */
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('form:forms:create')
                || !$this->get('mautic.security')->hasEntityAccess(
                    'form:forms:viewown',
                    'form:forms:viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setIsPublished(false);

            // Clone the forms's fields
            $fields = $entity->getFields()->toArray();
            /** @var \Mautic\FormBundle\Entity\Field $field */
            foreach ($fields as $field) {
                $fieldClone = clone $field;
                $fieldClone->setForm($entity);
                $fieldClone->setSessionId(null);
                $entity->addField($field->getId(), $fieldClone);
            }

            // Clone the forms's actions
            $actions = $entity->getActions()->toArray();
            /** @var \Mautic\FormBundle\Entity\Action $action */
            foreach ($actions as $action) {
                $actionClone = clone $action;
                $actionClone->setForm($entity);
                $entity->addAction($action->getId(), $actionClone);
            }
        }

        return $this->editAction($entity, true, true);
    }

    /**
     * Gives a preview of the form
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function previewAction($objectId)
    {
        $model = $this->getModel('form.form');
        $form  = $model->getEntity($objectId);

        if ($form === null) {
            $html =
                '<h1>'.
                $this->get('translator')->trans('mautic.form.error.notfound', array('%id%' => $objectId), 'flashes').
                '</h1>';
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'form:forms:editown',
            'form:forms:editother',
            $form->getCreatedBy()
        )
        ) {
            $html = '<h1>'.$this->get('translator')->trans('mautic.core.error.accessdenied', array(), 'flashes').'</h1>';
        } else {
            $html = $model->getContent($form, true, false);
        }

        $model->populateValuesWithGetParameters($form, $html);

        $viewParams = array(
            'content'     => $html,
            'stylesheets' => array(),
            'name'        => $form->getName()
        );

        $template = $form->getTemplate();
        if (!empty($template)) {
            $theme = $this->factory->getTheme($template);
            if ($theme->getTheme() != $template) {
                $config = $theme->getConfig();
                if (in_array('form', $config['features'])) {
                    $template = $theme->getTheme();
                } else {
                    $template = null;
                }
            }
        }

        $viewParams['template'] = $template;

        if (!empty($template)) {
            $logicalName     = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':form.html.php');
            $assetsHelper    = $this->factory->getHelper('template.assets');
            $slotsHelper     = $this->factory->getHelper('template.slots');
            $analyticsHelper = $this->factory->getHelper('template.analytics');

            if (!empty($customStylesheets)) {
                foreach ($customStylesheets as $css) {
                    $assetsHelper->addStylesheet($css);
                }
            }

            $slotsHelper->set('pageTitle', $form->getName());

            $analytics = $analyticsHelper->getCode();

            if (!empty($analytics)) {
                $assetsHelper->addCustomDeclaration($analytics);
            }

            return $this->render($logicalName, $viewParams);
        }

        return $this->render('MauticFormBundle::form.html.php', $viewParams);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticFormBundle:Form:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('form.form');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.form.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'form:forms:deleteown',
                'form:forms:deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'form.form');
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $identifier,
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->get('session')->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticFormBundle:Form:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel('form');
            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
                    'form:forms:deleteown',
                    'form:forms:deleteother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'form.form', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.form.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }

    /**
     * Clear field and actions from the session
     */
    public function clearSessionComponents($sessionId)
    {
        $session = $this->get('session');
        $session->remove('mautic.form.'.$sessionId.'.fields.modified');
        $session->remove('mautic.form.'.$sessionId.'.fields.deleted');
        $session->remove('mautic.form.'.$sessionId.'.fields.leadfields');

        $session->remove('mautic.form.'.$sessionId.'.actions.modified');
        $session->remove('mautic.form.'.$sessionId.'.actions.deleted');
    }

    /**
     *
     */
    public function batchRebuildHtmlAction()
    {
        $page      = $this->get('session')->get('mautic.form.page', 1);
        $returnUrl = $this->generateUrl('mautic_form_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticFormBundle:Form:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_form_index',
                'mauticContent' => 'form'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\FormBundle\Model\FormModel $model */
            $model = $this->getModel('form');
            $ids   = json_decode($this->request->query->get('ids', ''));
            $count = 0;
            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.form.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
                    'form:forms:editown',
                    'form:forms:editother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'form.form', true);
                } else {
                    $model->generateHtml($entity);
                    $count++;
                }
            }

            $flashes[] = array(
                'type'    => 'notice',
                'msg'     => 'mautic.form.notice.batch_html_generated',
                'msgVars' => array(
                    'pluralCount' => $count,
                    '%count%'     => $count
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }
}
