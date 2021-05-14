<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEntityEvent;
use Mautic\ApiBundle\Helper\BatchIdToEntityHelper;
use Mautic\ApiBundle\Serializer\Exclusion\ParentChildrenExclusionStrategy;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Exception\PermissionException;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CommonApiController.
 */
class CommonApiController extends AbstractFOSRestController implements MauticController
{
    use RequestTrait;
    use FormErrorMessagesTrait;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * If set to true, serializer will not return null values.
     *
     * @var bool
     */
    protected $customSelectRequested = false;

    /**
     * @var array
     */
    protected $dataInputMasks = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Class for the entity.
     *
     * @var string
     */
    protected $entityClass;

    /**
     * Key to return for entity lists.
     *
     * @var string
     */
    protected $entityNameMulti;

    /**
     * Key to return for a single entity.
     *
     * @var string
     */
    protected $entityNameOne;

    /**
     * Custom JMS strategies to add to the view's context.
     *
     * @var array
     */
    protected $exclusionStrategies = [];

    /**
     * Pass to the model's getEntities() method.
     *
     * @var array
     */
    protected $extraGetEntitiesArguments = [];

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var bool
     */
    protected $inBatchMode = false;

    /**
     * Used to set default filters for entity lists such as restricting to owning user.
     *
     * @var array
     */
    protected $listFilters = [];

    /**
     * Model object for processing the entity.
     *
     * @var \Mautic\CoreBundle\Model\AbstractCommonModel
     */
    protected $model;

    /**
     * The level parent/children should stop loading if applicable.
     *
     * @var int
     */
    protected $parentChildrenLevelDepth = 3;

    /**
     * Permission base for the entity such as page:pages.
     *
     * @var string
     */
    protected $permissionBase;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $routeParams = [];

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     * @var array
     */
    protected $serializerGroups = [];

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $entityRequestParameters = [];

    /**
     * Delete a batch of entities.
     *
     * @return array|Response
     */
    public function deleteEntitiesAction()
    {
        $parameters = $this->request->query->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $errors            = [];
        $entities          = $this->getBatchEntities($parameters, $errors, true);
        $this->inBatchMode = true;

        // Generate the view before deleting so that the IDs are still populated before Doctrine removes them
        $payload = [$this->entityNameMulti => $entities];
        $view    = $this->view($payload, Response::HTTP_OK);
        $this->setSerializationContext($view);
        $response = $this->handleView($view);

        foreach ($entities as $key => $entity) {
            if (null === $entity || !$entity->getId()) {
                $this->setBatchError($key, 'mautic.core.error.notfound', Response::HTTP_NOT_FOUND, $errors, $entities, $entity);
                continue;
            }

            if (!$this->checkEntityAccess($entity, 'delete')) {
                $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                continue;
            }

            $this->model->deleteEntity($entity);
            $this->getDoctrine()->getManager()->detach($entity);
        }

        if (!empty($errors)) {
            $content           = json_decode($response->getContent(), true);
            $content['errors'] = $errors;
            $response->setContent(json_encode($content));
        }

        return $response;
    }

    /**
     * Deletes an entity.
     *
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function deleteEntityAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'delete')) {
                return $this->accessDenied();
            }

            $this->model->deleteEntity($entity);

            $this->preSerializeEntity($entity);
            $view = $this->view([$this->entityNameOne => $entity], Response::HTTP_OK);
            $this->setSerializationContext($view);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Edit a batch of entities.
     *
     * @return array|Response
     */
    public function editEntitiesAction()
    {
        $parameters = $this->request->request->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $errors      = [];
        $statusCodes = [];
        $entities    = $this->getBatchEntities($parameters, $errors);

        foreach ($parameters as $key => $params) {
            $method = $this->request->getMethod();
            $entity = (isset($entities[$key])) ? $entities[$key] : null;

            $statusCode = Response::HTTP_OK;
            if (null === $entity || !$entity->getId()) {
                if ('PATCH' === $method) {
                    //PATCH requires that an entity exists
                    $this->setBatchError($key, 'mautic.core.error.notfound', Response::HTTP_NOT_FOUND, $errors, $entities, $entity);
                    $statusCodes[$key] = Response::HTTP_NOT_FOUND;
                    continue;
                }

                //PUT can create a new entity if it doesn't exist
                $entity = $this->model->getEntity();
                if (!$this->checkEntityAccess($entity, 'create')) {
                    $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                    $statusCodes[$key] = Response::HTTP_FORBIDDEN;
                    continue;
                }

                $statusCode = Response::HTTP_CREATED;
            }

            if (!$this->checkEntityAccess($entity, 'edit')) {
                $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                $statusCodes[$key] = Response::HTTP_FORBIDDEN;
                continue;
            }

            $this->processBatchForm($key, $entity, $params, $method, $errors, $entities);

            if (isset($errors[$key])) {
                $statusCodes[$key] = $errors[$key]['code'];
            } else {
                $statusCodes[$key] = $statusCode;
            }
        }

        $payload = [
            $this->entityNameMulti => $entities,
            'statusCodes'          => $statusCodes,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $view = $this->view($payload, Response::HTTP_OK);
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    /**
     * Edits an existing entity or creates one on PUT if it doesn't exist.
     *
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function editEntityAction($id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $this->request->request->all();
        $method     = $this->request->getMethod();

        if (null === $entity || !$entity->getId()) {
            if ('PATCH' === $method) {
                //PATCH requires that an entity exists
                return $this->notFound();
            }

            //PUT can create a new entity if it doesn't exist
            $entity = $this->model->getEntity();
            if (!$this->checkEntityAccess($entity, 'create')) {
                return $this->accessDenied();
            }
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        return $this->processForm($entity, $parameters, $method);
    }

    /**
     * Obtains a list of entities as defined by the API URL.
     *
     * @return Response
     */
    public function getEntitiesAction()
    {
        $repo          = $this->model->getRepository();
        $tableAlias    = $repo->getTableAlias();
        $publishedOnly = $this->request->get('published', 0);
        $minimal       = $this->request->get('minimal', 0);

        try {
            if (!$this->security->isGranted($this->permissionBase.':view')) {
                return $this->accessDenied();
            }
        } catch (PermissionException $e) {
            return $this->accessDenied($e->getMessage());
        }

        if ($this->security->checkPermissionExists($this->permissionBase.':viewother')
            && !$this->security->isGranted($this->permissionBase.':viewother')
        ) {
            $this->listFilters[] = [
                'column' => $tableAlias.'.createdBy',
                'expr'   => 'eq',
                'value'  => $this->user->getId(),
            ];
        }

        if ($publishedOnly) {
            $this->listFilters[] = [
                'column' => $tableAlias.'.isPublished',
                'expr'   => 'eq',
                'value'  => true,
            ];
        }

        if ($minimal) {
            if (isset($this->serializerGroups[0])) {
                $this->serializerGroups[0] = str_replace('Details', 'List', $this->serializerGroups[0]);
            }
        }

        $args = array_merge(
            [
                'start'  => $this->request->query->get('start', 0),
                'limit'  => $this->request->query->get('limit', $this->coreParametersHelper->get('default_pagelimit')),
                'filter' => [
                    'string' => $this->request->query->get('search', ''),
                    'force'  => $this->listFilters,
                ],
                'orderBy'        => $this->addAliasIfNotPresent($this->request->query->get('orderBy', ''), $tableAlias),
                'orderByDir'     => $this->request->query->get('orderByDir', 'ASC'),
                'withTotalCount' => true, //for repositories that break free of Paginator
            ],
            $this->extraGetEntitiesArguments
        );

        if ($select = InputHelper::cleanArray($this->request->get('select', []))) {
            $args['select']              = $select;
            $this->customSelectRequested = true;
        }

        if ($where = $this->getWhereFromRequest()) {
            $args['filter']['where'] = $where;
        }

        if ($order = $this->getOrderFromRequest()) {
            $args['filter']['order'] = $order;
        }

        $results = $this->model->getEntities($args);

        [$entities, $totalCount] = $this->prepareEntitiesForView($results);

        $view = $this->view(
            [
                'total'                => $totalCount,
                $this->entityNameMulti => $entities,
            ],
            Response::HTTP_OK
        );
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    /**
     * Sanitizes and returns an array of where statements from the request.
     *
     * @return array
     */
    protected function getWhereFromRequest()
    {
        $where = InputHelper::cleanArray($this->request->get('where', []));

        $this->sanitizeWhereClauseArrayFromRequest($where);

        return $where;
    }

    /**
     * Sanitizes and returns an array of ORDER statements from the request.
     *
     * @return array
     */
    protected function getOrderFromRequest()
    {
        return InputHelper::cleanArray($this->request->get('order', []));
    }

    /**
     * Adds the repository alias to the column name if it doesn't exist.
     *
     * @return string $column name with alias prefix
     */
    protected function addAliasIfNotPresent($columns, $alias)
    {
        if (!$columns) {
            return $columns;
        }

        $columns = explode(',', trim($columns));
        $prefix  = $alias.'.';

        array_walk(
            $columns,
            function (&$column, $key, $prefix) {
                $column = trim($column);
                if (1 === count(explode('.', $column))) {
                    $column = $prefix.$column;
                }
            },
            $prefix
        );

        return implode(',', $columns);
    }

    /**
     * Obtains a specific entity as defined by the API URL.
     *
     * @param int $id Entity ID
     *
     * @return Response
     */
    public function getEntityAction($id)
    {
        $args = [];
        if ($select = InputHelper::cleanArray($this->request->get('select', []))) {
            $args['select']              = $select;
            $this->customSelectRequested = true;
        }

        if (!empty($args)) {
            $args['id'] = $id;
            $entity     = $this->model->getEntity($args);
        } else {
            $entity = $this->model->getEntity($id);
        }

        if (!$entity instanceof $this->entityClass) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        $this->preSerializeEntity($entity);
        $view = $this->view([$this->entityNameOne => $entity], Response::HTTP_OK);
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    /**
     * Initialize some variables.
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->security = $this->get('mautic.security');

        if ($this->model && !$this->permissionBase && method_exists($this->model, 'getPermissionBase')) {
            $this->permissionBase = $this->model->getPermissionBase();
        }
    }

    /**
     * Creates new entity from provided params.
     *
     * @return object
     */
    public function getNewEntity(array $params)
    {
        return $this->model->getEntity();
    }

    /**
     * Create a batch of new entities.
     *
     * @return array|Response
     */
    public function newEntitiesAction()
    {
        $entity = $this->model->getEntity();

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        $parameters = $this->request->request->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $this->inBatchMode = true;
        $entities          = [];
        $errors            = [];
        $statusCodes       = [];
        foreach ($parameters as $key => $params) {
            // Can be new or an existing on based on params
            $entity       = $this->getNewEntity($params);
            $entityExists = false;
            $method       = 'POST';
            if ($entity->getId()) {
                $entityExists = true;
                $method       = 'PATCH';
                if (!$this->checkEntityAccess($entity, 'edit')) {
                    $this->setBatchError($key, 'mautic.core.error.accessdenied', Response::HTTP_FORBIDDEN, $errors, $entities, $entity);
                    $statusCodes[$key] = Response::HTTP_FORBIDDEN;
                    continue;
                }
            }
            $this->processBatchForm($key, $entity, $params, $method, $errors, $entities);

            if (isset($errors[$key])) {
                $statusCodes[$key] = $errors[$key]['code'];
            } elseif ($entityExists) {
                $statusCodes[$key] = Response::HTTP_OK;
            } else {
                $statusCodes[$key] = Response::HTTP_CREATED;
            }
        }

        $payload = [
            $this->entityNameMulti => $entities,
            'statusCodes'          => $statusCodes,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $view = $this->view($payload, Response::HTTP_CREATED);
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    /**
     * Creates a new entity.
     *
     * @return Response
     */
    public function newEntityAction()
    {
        $parameters = $this->request->request->all();
        $entity     = $this->getNewEntity($parameters);

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        return $this->processForm($entity, $parameters, 'POST');
    }

    public function setCoreParametersHelper(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setFlashBag(FlashBag $flashBag)
    {
        // @see \Mautic\CoreBundle\EventListener\CoreSubscriber::onKernelController()
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Alias for notFound method. It's used in the LeadAccessTrait.
     *
     * @param array $args
     *
     * @return Response
     */
    public function postActionRedirect($args = [])
    {
        return $this->notFound('mautic.contact.error.notfound');
    }

    /**
     * Returns a 403 Access Denied.
     *
     * @param string $msg
     *
     * @return Response
     */
    protected function accessDenied($msg = 'mautic.core.error.accessdenied')
    {
        return $this->returnError($msg, Response::HTTP_FORBIDDEN);
    }

    protected function addExclusionStrategy(ExclusionStrategyInterface $strategy)
    {
        $this->exclusionStrategies[] = $strategy;
    }

    /**
     * Returns a 400 Bad Request.
     *
     * @param string $msg
     *
     * @return Response
     */
    protected function badRequest($msg = 'mautic.core.error.badrequest')
    {
        return $this->returnError($msg, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Checks if user has permission to access retrieved entity.
     *
     * @param mixed  $entity
     * @param string $action view|create|edit|publish|delete
     *
     * @return bool|Response
     */
    protected function checkEntityAccess($entity, $action = 'view')
    {
        if ('create' != $action && method_exists($entity, 'getCreatedBy')) {
            $ownPerm   = "{$this->permissionBase}:{$action}own";
            $otherPerm = "{$this->permissionBase}:{$action}other";

            $owner = (method_exists($entity, 'getPermissionUser')) ? $entity->getPermissionUser() : $entity->getCreatedBy();

            return $this->security->hasEntityAccess($ownPerm, $otherPerm, $owner);
        }

        try {
            return $this->security->isGranted("{$this->permissionBase}:{$action}");
        } catch (PermissionException $e) {
            return $this->accessDenied($e->getMessage());
        }
    }

    /**
     * Creates the form instance.
     *
     * @param $entity
     *
     * @return Form
     */
    protected function createEntityForm($entity)
    {
        return $this->model->createForm(
            $entity,
            $this->get('form.factory'),
            null,
            array_merge(
                [
                    'csrf_protection'    => false,
                    'allow_extra_fields' => true,
                ],
                $this->getEntityFormOptions()
            )
        );
    }

    /**
     * @param        $parameters
     * @param        $errors
     * @param bool   $prepareForSerialization
     * @param string $requestIdColumn
     * @param null   $model
     * @param bool   $returnWithOriginalKeys
     *
     * @return array|mixed
     */
    protected function getBatchEntities($parameters, &$errors, $prepareForSerialization = false, $requestIdColumn = 'id', $model = null, $returnWithOriginalKeys = true)
    {
        $idHelper = new BatchIdToEntityHelper($parameters, $requestIdColumn);

        if (!$idHelper->hasIds()) {
            return [];
        }

        $model    = ($model) ? $model : $this->model;
        $entities = $model->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => $model->getRepository()->getTableAlias().'.id',
                            'expr'   => 'in',
                            'value'  => $idHelper->getIds(),
                        ],
                    ],
                ],
                'ignore_paginator' => true,
            ]
        );
        // It must be associative because the order of entities has changed
        $idHelper->setIsAssociative(true);

        [$entities, $total] = $prepareForSerialization
                ?
                $this->prepareEntitiesForView($entities)
                :
                $this->prepareEntityResultsToArray($entities);

        // Set errors
        if ($idHelper->hasErrors()) {
            foreach ($idHelper->getErrors() as $key => $error) {
                $this->setBatchError($key, $error, Response::HTTP_BAD_REQUEST, $errors);
            }
        }

        // Return the response with matching keys from the request
        if ($returnWithOriginalKeys) {
            if ($entities instanceof Paginator) {
                $entities = $entities->getIterator()->getArrayCopy();
            }

            return $idHelper->orderByOriginalKey($entities);
        }

        // Return the response with IDs as keys (default behavior)
        $return = [];
        foreach ($entities as $entity) {
            $return[$entity->getId()] = $entity;
        }

        return $return;
    }

    /**
     * Get the default properties of an entity and parents.
     *
     * @param $entity
     *
     * @return array
     */
    protected function getEntityDefaultProperties($entity)
    {
        $class         = get_class($entity);
        $chain         = array_reverse(class_parents($entity), true) + [$class => $class];
        $defaultValues = [];

        $classMetdata = new ClassMetadata($class);
        foreach ($chain as $class) {
            if (method_exists($class, 'loadMetadata')) {
                $class::loadMetadata($classMetdata);
            }
            $defaultValues += (new \ReflectionClass($class))->getDefaultProperties();
        }

        // These are the mapped columns
        $fields = $classMetdata->getFieldNames();

        // Merge values in with $fields
        $properties = [];
        foreach ($fields as $field) {
            $properties[$field] = $defaultValues[$field];
        }

        return $properties;
    }

    /**
     * Append options to the form.
     *
     * @return array
     */
    protected function getEntityFormOptions()
    {
        return [];
    }

    /**
     * Get a model instance from the service container.
     *
     * @param $modelNameKey
     *
     * @return AbstractCommonModel
     */
    protected function getModel($modelNameKey)
    {
        return $this->get('mautic.model.factory')->getModel($modelNameKey);
    }

    /**
     * Returns a 404 Not Found.
     *
     * @param string $msg
     *
     * @return Response
     */
    protected function notFound($msg = 'mautic.core.error.notfound')
    {
        return $this->returnError($msg, Response::HTTP_NOT_FOUND);
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before populating the form.
     *
     * @param        $entity
     * @param        $parameters
     * @param string $action
     *
     * @return mixed
     */
    protected function prePopulateForm(&$entity, $parameters, $action = 'edit')
    {
    }

    /**
     * Give the controller an opportunity to process the entity before persisting.
     *
     * @param $entity
     * @param $form
     * @param $parameters
     * @param $action
     *
     * @return mixed
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before going through serializer.
     *
     * @param        $entity
     * @param string $action
     *
     * @return mixed
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
    }

    /**
     * Prepares entities returned from repository getEntities().
     *
     * @param $results
     *
     * @return array($entities, $totalCount)
     */
    protected function prepareEntitiesForView($results)
    {
        return $this->prepareEntityResultsToArray(
            $results,
            function ($entity) {
                $this->preSerializeEntity($entity);
            }
        );
    }

    /**
     * @param      $results
     * @param null $callback
     *
     * @return array($entities, $totalCount)
     */
    protected function prepareEntityResultsToArray($results, $callback = null)
    {
        if (is_array($results) && isset($results['count'])) {
            $totalCount = $results['count'];
            $results    = $results['results'];
        } else {
            $totalCount = count($results);
        }

        $entityResultHelper = $this->get('mautic.api.helper.entity_result');

        $entities = $entityResultHelper->getArray($results, $callback);

        return [$entities, $totalCount];
    }

    /**
     * Convert posted parameters into what the form needs in order to successfully bind.
     *
     * @param $parameters
     * @param $entity
     * @param $action
     *
     * @return mixed
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        return $parameters;
    }

    /**
     * @param $key
     * @param $entity
     * @param $params
     * @param $method
     * @param $errors
     * @param $entities
     */
    protected function processBatchForm($key, $entity, $params, $method, &$errors, &$entities)
    {
        $this->inBatchMode = true;
        $formResponse      = $this->processForm($entity, $params, $method);
        if ($formResponse instanceof Response) {
            if (!$formResponse instanceof RedirectResponse) {
                // Assume an error
                $this->setBatchError(
                    $key,
                    InputHelper::string($formResponse->getContent()),
                    $formResponse->getStatusCode(),
                    $errors,
                    $entities,
                    $entity
                );
            }
        } elseif (is_object($formResponse) && get_class($formResponse) === get_class($entity)) {
            // Success
            $entities[$key] = $formResponse;
        } elseif (is_array($formResponse) && isset($formResponse['code'], $formResponse['message'])) {
            // There was an error
            $errors[$key] = $formResponse;
        }

        $this->getDoctrine()->getManager()->detach($entity);

        $this->inBatchMode = false;
    }

    /**
     * Processes API Form.
     *
     * @param        $entity
     * @param null   $parameters
     * @param string $method
     *
     * @return mixed
     */
    protected function processForm($entity, $parameters = null, $method = 'PUT')
    {
        $categoryId = null;

        if (null === $parameters) {
            //get from request
            $parameters = $this->request->request->all();
        }

        // Store the original parameters from the request so that callbacks can have access to them as needed
        $this->entityRequestParameters = $parameters;

        //unset the ID in the parameters if set as this will cause the form to fail
        if (isset($parameters['id'])) {
            unset($parameters['id']);
        }

        //is an entity being updated or created?
        if ($entity->getId()) {
            $statusCode = Response::HTTP_OK;
            $action     = 'edit';
        } else {
            $statusCode = Response::HTTP_CREATED;
            $action     = 'new';

            // All the properties have to be defined in order for validation to work
            // Bug reported https://github.com/symfony/symfony/issues/19788
            $defaultProperties = $this->getEntityDefaultProperties($entity);
            $parameters        = array_merge($defaultProperties, $parameters);
        }

        // Check if user has access to publish
        if (
            (
                array_key_exists('isPublished', $parameters) ||
                array_key_exists('publishUp', $parameters) ||
                array_key_exists('publishDown', $parameters)
            ) &&
            $this->security->checkPermissionExists($this->permissionBase.':publish')) {
            if ($this->security->checkPermissionExists($this->permissionBase.':publishown')) {
                if (!$this->checkEntityAccess($entity, 'publish')) {
                    if ('new' === $action) {
                        $parameters['isPublished'] = 0;
                    } else {
                        unset($parameters['isPublished'], $parameters['publishUp'], $parameters['publishDown']);
                    }
                }
            }
        }

        $form         = $this->createEntityForm($entity);
        $submitParams = $this->prepareParametersForBinding($parameters, $entity, $action);

        if ($submitParams instanceof Response) {
            return $submitParams;
        }

        // Remove category from the payload because it will cause form validation error.
        if (isset($submitParams['category'])) {
            $categoryId = (int) $submitParams['category'];
            unset($submitParams['category']);
        }

        $this->prepareParametersFromRequest($form, $submitParams, $entity, $this->dataInputMasks);

        $form->submit($submitParams, 'PATCH' !== $method);

        if ($form->isValid()) {
            $this->setCategory($entity, $categoryId);
            $preSaveError = $this->preSaveEntity($entity, $form, $submitParams, $action);

            if ($preSaveError instanceof Response) {
                return $preSaveError;
            }

            try {
                if ($this->dispatcher->hasListeners(ApiEvents::API_ON_ENTITY_PRE_SAVE)) {
                    $this->dispatcher->dispatch(ApiEvents::API_ON_ENTITY_PRE_SAVE, new ApiEntityEvent($entity, $this->entityRequestParameters, $this->request));
                }
            } catch (\Exception $e) {
                return $this->returnError($e->getMessage(), $e->getCode());
            }

            $this->model->saveEntity($entity);
            $headers = [];
            //return the newly created entities location if applicable
            if (Response::HTTP_CREATED === $statusCode) {
                $route = (null !== $this->get('router')->getRouteCollection()->get('mautic_api_'.$this->entityNameMulti.'_getone'))
                    ? 'mautic_api_'.$this->entityNameMulti.'_getone' : 'mautic_api_get'.$this->entityNameOne;
                $headers['Location'] = $this->generateUrl(
                    $route,
                    array_merge(['id' => $entity->getId()], $this->routeParams),
                    true
                );
            }

            try {
                if ($this->dispatcher->hasListeners(ApiEvents::API_ON_ENTITY_POST_SAVE)) {
                    $this->dispatcher->dispatch(ApiEvents::API_ON_ENTITY_POST_SAVE, new ApiEntityEvent($entity, $this->entityRequestParameters, $this->request));
                }
            } catch (\Exception $e) {
                return $this->returnError($e->getMessage(), $e->getCode());
            }

            $this->preSerializeEntity($entity, $action);

            if ($this->inBatchMode) {
                return $entity;
            } else {
                $view = $this->view([$this->entityNameOne => $entity], $statusCode, $headers);
            }

            $this->setSerializationContext($view);
        } else {
            $formErrors = $this->getFormErrorMessages($form);
            $msg        = $this->getFormErrorMessage($formErrors);

            if (!$msg) {
                $msg = $this->translator->trans('mautic.core.error.badrequest', [], 'flashes');
            }

            return $this->returnError($msg, Response::HTTP_BAD_REQUEST, $formErrors);
        }

        return $this->handleView($view);
    }

    /**
     * Returns an error.
     *
     * @param string $msg
     * @param int    $code
     * @param array  $details
     *
     * @return Response|array
     */
    protected function returnError($msg, $code = Response::HTTP_INTERNAL_SERVER_ERROR, $details = [])
    {
        if ($this->get('translator')->hasId($msg, 'flashes')) {
            $msg = $this->get('translator')->trans($msg, [], 'flashes');
        } elseif ($this->get('translator')->hasId($msg, 'messages')) {
            $msg = $this->get('translator')->trans($msg, [], 'messages');
        }

        $error = [
            'code'    => $code,
            'message' => $msg,
            'details' => $details,
            'type'    => null,
        ];

        if ($this->inBatchMode) {
            return $error;
        }

        $view = $this->view(
            [
                'errors' => [
                    $error,
                ],
            ],
            $code
        );

        return $this->handleView($view);
    }

    /**
     * @param $where
     */
    protected function sanitizeWhereClauseArrayFromRequest(&$where)
    {
        foreach ($where as $key => $statement) {
            if (isset($statement['internal'])) {
                unset($where[$key]);
            } elseif (in_array($statement['expr'], ['andX', 'orX'])) {
                $this->sanitizeWhereClauseArrayFromRequest($statement['val']);
            }
        }
    }

    /**
     * @param object $entity
     * @param int    $categoryId
     *
     * @throws \UnexpectedValueException
     */
    protected function setCategory($entity, $categoryId)
    {
        if (!empty($categoryId) && method_exists($entity, 'setCategory')) {
            $category = $this->getDoctrine()->getManager()->find(Category::class, $categoryId);

            if (null === $category) {
                throw new \UnexpectedValueException("Category $categoryId does not exist");
            }

            $entity->setCategory($category);
        }
    }

    /**
     * @param       $key
     * @param       $msg
     * @param       $code
     * @param       $errors
     * @param array $entities
     * @param null  $entity
     */
    protected function setBatchError($key, $msg, $code, &$errors, &$entities = [], $entity = null)
    {
        unset($entities[$key]);
        if ($entity) {
            $this->getDoctrine()->getManager()->detach($entity);
        }

        $errors[$key] = [
            'message' => $this->get('translator')->hasId($msg, 'flashes') ? $this->get('translator')->trans($msg, [], 'flashes') : $msg,
            'code'    => $code,
            'type'    => 'api',
        ];
    }

    /**
     * Set serialization groups and exclusion strategies.
     *
     * @param View $view
     */
    protected function setSerializationContext($view)
    {
        $context = $view->getContext();
        if (!empty($this->serializerGroups)) {
            $context->setGroups($this->serializerGroups);
        }

        // Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
            new PublishDetailsExclusionStrategy()
        );

        // Only include first level of children/parents
        if ($this->parentChildrenLevelDepth) {
            $context->addExclusionStrategy(
                new ParentChildrenExclusionStrategy($this->parentChildrenLevelDepth)
            );
        }

        // Add custom exclusion strategies
        foreach ($this->exclusionStrategies as $strategy) {
            $context->addExclusionStrategy($strategy);
        }

        // Include null values if a custom select has not been given
        if (!$this->customSelectRequested) {
            $context->setSerializeNull(true);
        }

        $view->setContext($context);
    }

    /**
     * @param $parameters
     *
     * @return array|bool|Response
     */
    protected function validateBatchPayload($parameters)
    {
        $batchLimit = (int) $this->get('mautic.config')->getParameter('api_batch_max_limit', 200);
        if (count($parameters) > $batchLimit) {
            return $this->returnError($this->get('translator')->trans('mautic.api.call.batch_exception', ['%limit%' => $batchLimit]));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param null $data
     * @param null $statusCode
     */
    protected function view($data = null, $statusCode = null, array $headers = [])
    {
        if ($data instanceof Paginator) {
            // Get iterator out of Paginator class so that the entities are properly serialized by the serializer
            $data = $data->getIterator()->getArrayCopy();
        }

        $headers['Mautic-Version'] = $this->get('kernel')->getVersion();

        return parent::view($data, $statusCode, $headers);
    }
}
