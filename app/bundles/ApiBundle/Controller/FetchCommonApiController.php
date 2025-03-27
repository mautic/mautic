<?php

namespace Mautic\ApiBundle\Controller;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiSerializationContextEvent;
use Mautic\ApiBundle\Helper\BatchIdToEntityHelper;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\ApiBundle\Serializer\Exclusion\ParentChildrenExclusionStrategy;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\CoreBundle\Controller\MauticController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\RequestTrait;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\CoreBundle\Security\Exception\PermissionException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template E of object
 */
class FetchCommonApiController extends AbstractFOSRestController implements MauticController
{
    use RequestTrait;
    use FormErrorMessagesTrait;

    /**
     * If set to true, serializer will not return null values.
     *
     * @var bool
     */
    protected $customSelectRequested = false;

    /**
     * Class for the entity.
     *
     * @var class-string<E>
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
     * @var array<int, ExclusionStrategyInterface>
     */
    protected $exclusionStrategies = [];

    /**
     * Pass to the model's getEntities() method.
     *
     * @var array<mixed>
     */
    protected $extraGetEntitiesArguments = [];

    /**
     * @var bool
     */
    protected $inBatchMode = false;

    /**
     * Used to set default filters for entity lists such as restricting to owning user.
     *
     * @var array<array<string, mixed>>
     */
    protected $listFilters = [];

    /**
     * Model object for processing the entity.
     *
     * @var AbstractCommonModel<E>|null
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
     * @var string|null
     */
    protected $permissionBase;

    /**
     * @var array<int, string>
     */
    protected $serializerGroups = [];

    /**
     * @var Translator
     */
    protected $translator;

    protected ContainerBagInterface $parametersContainer;

    /**
     * @param ModelFactory<E> $modelFactory
     */
    public function __construct(
        protected CorePermissions $security,
        Translator $translator,
        protected EntityResultHelper $entityResultHelper,
        private AppVersion $appVersion,
        private RequestStack $requestStack,
        protected ManagerRegistry $doctrine,
        protected ModelFactory $modelFactory,
        protected EventDispatcherInterface $dispatcher,
        protected CoreParametersHelper $coreParametersHelper,
    ) {
        $this->translator           = $translator;

        if (null !== $this->model && !$this->permissionBase && method_exists($this->model, 'getPermissionBase')) {
            $this->permissionBase = $this->model->getPermissionBase();
        }
    }

    /**
     * Obtains a list of entities as defined by the API URL.
     *
     * @return Response
     */
    public function getEntitiesAction(Request $request, UserHelper $userHelper)
    {
        $repo          = $this->model->getRepository();
        $tableAlias    = $repo->getTableAlias();
        $publishedOnly = $request->get('published', 0);
        $minimal       = $request->get('minimal', 0);

        try {
            if (!$this->security->isGranted($this->permissionBase.':view')) {
                return $this->accessDenied();
            }
        } catch (PermissionException $e) {
            return $this->accessDenied($e->getMessage());
        }

        if ($this->security->checkPermissionExists($this->permissionBase.':viewother')
            && !$this->security->isGranted($this->permissionBase.':viewother')
            && null !== $user = $userHelper->getUser()
        ) {
            $this->listFilters[] = [
                'column' => $tableAlias.'.createdBy',
                'expr'   => 'eq',
                'value'  => $user->getId(),
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
                'start'  => $request->query->get('start', 0),
                'limit'  => $request->query->get('limit', $this->coreParametersHelper->get('default_pagelimit')),
                'filter' => [
                    'string' => $request->query->get('search', ''),
                    'force'  => $this->listFilters,
                ],
                'orderBy'        => $this->addAliasIfNotPresent($request->query->get('orderBy', ''), $tableAlias),
                'orderByDir'     => $request->query->get('orderByDir', 'ASC'),
                'withTotalCount' => true, // for repositories that break free of Paginator
            ],
            $this->extraGetEntitiesArguments
        );

        if ($select = InputHelper::cleanArray($request->query->all()['select'] ?? $request->request->all()['select'] ?? [])) {
            $args['select']              = $select;
            $this->customSelectRequested = true;
        }

        if ($where = $this->getWhereFromRequest($request)) {
            $args['filter']['where'] = $where;
        }

        if ($order = $this->getOrderFromRequest($request)) {
            $args['filter']['order'] = $order;
        }

        if ($totalCountTtl = $this->getTotalCountTtl()) {
            $args['totalCountTtl'] = $totalCountTtl;
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
     * @return array<mixed>
     */
    protected function getWhereFromRequest(Request $request)
    {
        $where = $request->query->all()['where'] ?? [];

        $this->sanitizeWhereClauseArrayFromRequest($where);

        return $where;
    }

    /**
     * Sanitizes and returns an array of ORDER statements from the request.
     *
     * @return array<mixed>
     */
    protected function getOrderFromRequest(Request $request): array
    {
        return InputHelper::cleanArray($request->query->all()['order'] ?? []);
    }

    /**
     * Adds the repository alias to the column name if it doesn't exist.
     *
     * @return string $column name with alias prefix
     */
    protected function addAliasIfNotPresent(string $columns, string $alias): string
    {
        if (!$columns) {
            return $columns;
        }

        $columns = explode(',', trim($columns));
        $prefix  = $alias.'.';

        array_walk(
            $columns,
            function (&$column, $key, $prefix): void {
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
    public function getEntityAction(Request $request, $id)
    {
        $args = [];
        if ($select = InputHelper::cleanArray($request->get('select', []))) {
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
     * Creates new entity from provided params.
     *
     * @param array<mixed> $params
     *
     * @return object
     */
    public function getNewEntity(array $params)
    {
        return $this->model->getEntity();
    }

    public function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('Request is not set.');
        }

        return $request;
    }

    /**
     * Alias for notFound method. It's used in the LeadAccessTrait.
     *
     * @param array<mixed> $args
     *
     * @return Response
     */
    public function postActionRedirect(array $args = [])
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

    protected function addExclusionStrategy(ExclusionStrategyInterface $strategy): void
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
        if ('create' !== $action && is_object($entity) && method_exists($entity, 'getCreatedBy')) {
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
     * @param mixed[]                   $parameters
     * @param mixed[]                   $errors
     * @param bool                      $prepareForSerialization
     * @param string                    $requestIdColumn
     * @param MauticModelInterface|null $model
     * @param bool                      $returnWithOriginalKeys
     *
     * @return mixed[]
     */
    protected function getBatchEntities($parameters, &$errors, $prepareForSerialization = false, $requestIdColumn = 'id', $model = null, $returnWithOriginalKeys = true): array
    {
        $idHelper = new BatchIdToEntityHelper($parameters, $requestIdColumn);

        if (!$idHelper->hasIds()) {
            return [];
        }

        /** @var AbstractCommonModel<object> $model */
        $model    = $model ?: $this->model;
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
            if ($entities instanceof \ArrayObject) {
                $entities = $entities->getArrayCopy();
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
     * @phpstan-param E $entity
     *
     * @return array<mixed>
     */
    protected function getEntityDefaultProperties(object $entity): array
    {
        $class         = $entity::class;
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
     * @return array<string, mixed>
     */
    protected function getEntityFormOptions(): array
    {
        return [];
    }

    /**
     * Get a model instance from the service container.
     *
     * @return AbstractCommonModel<E>
     */
    protected function getModel(string $modelNameKey): AbstractCommonModel
    {
        return $this->modelFactory->getModel($modelNameKey);
    }

    /**
     * Returns a 404 Not Found.
     *
     * @return Response
     */
    protected function notFound(string $msg = 'mautic.core.error.notfound')
    {
        return $this->returnError($msg, Response::HTTP_NOT_FOUND);
    }

    /**
     * Gives child controllers opportunity to analyze and do whatever to an entity before going through serializer.
     *
     * @phpstan-param E $entity
     */
    protected function preSerializeEntity(object $entity, string $action = 'view'): void
    {
    }

    /**
     * Prepares entities returned from repository getEntities().
     *
     * @param array<mixed>|Paginator<E> $results
     *
     * @return array{0: array<mixed>|\ArrayObject<int,mixed>, 1: int}
     */
    protected function prepareEntitiesForView($results): array
    {
        return $this->prepareEntityResultsToArray(
            $results,
            function ($entity): void {
                $this->preSerializeEntity($entity);
            }
        );
    }

    /**
     * @param array<mixed>|Paginator<E> $results
     * @param callable|null             $callback
     *
     * @return array{0: array<mixed>|\ArrayObject<int,mixed>, 1: int}
     */
    protected function prepareEntityResultsToArray($results, $callback = null): array
    {
        if (is_array($results) && isset($results['count'])) {
            $totalCount = $results['count'];
            $results    = $results['results'];
        } else {
            $totalCount = count($results);
        }

        $entities = $this->entityResultHelper->getArray($results, $callback);

        return [$entities, $totalCount];
    }

    /**
     * Returns an error.
     *
     * @param array<mixed> $details
     *
     * @return Response|array<string, array<mixed>|int|string|null>
     */
    protected function returnError(string $msg, int $code = Response::HTTP_INTERNAL_SERVER_ERROR, array $details = [])
    {
        if ($this->translator->hasId($msg, 'flashes')) {
            $msg = $this->translator->trans($msg, [], 'flashes');
        } elseif ($this->translator->hasId($msg, 'messages')) {
            $msg = $this->translator->trans($msg, [], 'messages');
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
     * @param array<mixed> $where
     */
    protected function sanitizeWhereClauseArrayFromRequest(array &$where): void
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
     * @param array<int, array<string|int>> $errors
     * @param array<int, object|null>       $entities
     *
     * @phpstan-param E|null $entity
     * @phpstan-param array<int, E|null> $entities
     */
    protected function setBatchError(int $key, string $msg, int $code, array &$errors, array &$entities = [], ?object $entity = null): void
    {
        unset($entities[$key]);
        if ($entity) {
            $this->doctrine->getManager()->detach($entity);
        }

        $errors[$key] = [
            'message' => $this->translator->hasId($msg, 'flashes') ? $this->translator->trans($msg, [], 'flashes') : $msg,
            'code'    => $code,
            'type'    => 'api',
        ];
    }

    /**
     * Set serialization groups and exclusion strategies.
     */
    protected function setSerializationContext(View $view): void
    {
        $context = $view->getContext();

        if ($this->dispatcher->hasListeners(ApiEvents::API_PRE_SERIALIZATION_CONTEXT)) {
            $apiSerializationContextEvent = new ApiSerializationContextEvent($context, $this->getCurrentRequest());
            $this->dispatcher->dispatch($apiSerializationContextEvent, ApiEvents::API_PRE_SERIALIZATION_CONTEXT);
            $context = $apiSerializationContextEvent->getContext();
        }

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

        if ($this->dispatcher->hasListeners(ApiEvents::API_POST_SERIALIZATION_CONTEXT)) {
            $apiSerializationContextEvent = new ApiSerializationContextEvent($context, $this->getCurrentRequest());
            $this->dispatcher->dispatch($apiSerializationContextEvent, ApiEvents::API_POST_SERIALIZATION_CONTEXT);
            $context = $apiSerializationContextEvent->getContext();
        }

        $view->setContext($context);
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return array<string, array<mixed>|int|string|null>|bool|Response
     */
    protected function validateBatchPayload(array $parameters)
    {
        $batchLimit = (int) $this->coreParametersHelper->get('api_batch_max_limit', 200);
        if (count($parameters) > $batchLimit) {
            return $this->returnError($this->translator->trans('mautic.api.call.batch_exception', ['%limit%' => $batchLimit]));
        }

        return true;
    }

    /**
     * @param mixed|null                $data
     * @param array<string, string|int> $headers
     */
    protected function view($data = null, ?int $statusCode = null, array $headers = []): View
    {
        if ($data instanceof Paginator) {
            // Get iterator out of Paginator class so that the entities are properly serialized by the serializer
            $data = iterator_to_array($data->getIterator(), true);
        }

        $headers['Mautic-Version'] = $this->appVersion->getVersion();

        return parent::view($data, $statusCode, $headers);
    }

    protected function getTotalCountTtl(): ?int
    {
        return null;
    }
}
