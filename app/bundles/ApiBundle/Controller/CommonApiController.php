<?php

namespace Mautic\ApiBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\View\View;
use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\ApiEntityEvent;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @template E of object
 *
 * @extends FetchCommonApiController<E>
 */
class CommonApiController extends FetchCommonApiController
{
    /**
     * @var array
     */
    protected $dataInputMasks = [];

    /**
     * Model object for processing the entity.
     *
     * @var FormModel<E>|null
     */
    protected $model;

    /**
     * @var array
     */
    protected $routeParams = [];

    /**
     * @var array
     */
    protected $entityRequestParameters = [];

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        protected RouterInterface $router,
        protected FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        MauticFactory $factory
    ) {
        parent::__construct($security, $translator, $entityResultHelper, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
    }

    /**
     * Delete a batch of entities.
     *
     * @return array|Response
     */
    public function deleteEntitiesAction(Request $request)
    {
        $parameters = $request->query->all();

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
            $this->doctrine->getManager()->detach($entity);
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
    public function editEntitiesAction(Request $request)
    {
        $parameters = $request->request->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $errors      = [];
        $statusCodes = [];
        $entities    = $this->getBatchEntities($parameters, $errors);

        foreach ($parameters as $key => $params) {
            $method = $request->getMethod();
            $entity = $entities[$key] ?? null;

            $statusCode = Response::HTTP_OK;
            if (null === $entity || !$entity->getId()) {
                if ('PATCH' === $method) {
                    // PATCH requires that an entity exists
                    $this->setBatchError($key, 'mautic.core.error.notfound', Response::HTTP_NOT_FOUND, $errors, $entities, $entity);
                    $statusCodes[$key] = Response::HTTP_NOT_FOUND;
                    continue;
                }

                // PUT can create a new entity if it doesn't exist
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

            $this->processBatchForm($request, $key, $entity, $params, $method, $errors, $entities);

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
    public function editEntityAction(Request $request, $id)
    {
        $entity     = $this->model->getEntity($id);
        $parameters = $request->request->all();
        $method     = $request->getMethod();

        if (null === $entity || !$entity->getId()) {
            if ('PATCH' === $method) {
                // PATCH requires that an entity exists
                return $this->notFound();
            }

            // PUT can create a new entity if it doesn't exist
            $entity = $this->model->getEntity();
            if (!$this->checkEntityAccess($entity, 'create')) {
                return $this->accessDenied();
            }
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        return $this->processForm($request, $entity, $parameters, $method);
    }

    /**
     * Create a batch of new entities.
     *
     * @return array|Response
     */
    public function newEntitiesAction(Request $request)
    {
        $entity = $this->model->getEntity();

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        $parameters = $request->request->all();

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
            $this->processBatchForm($request, $key, $entity, $params, $method, $errors, $entities);

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
    public function newEntityAction(Request $request)
    {
        $parameters = $request->request->all();
        $entity     = $this->getNewEntity($parameters);

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        return $this->processForm($request, $entity, $parameters, 'POST');
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function createEntityForm($entity): FormInterface
    {
        return $this->model->createForm(
            $entity,
            $this->formFactory,
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
     * Gives child controllers opportunity to analyze and do whatever to an entity before populating the form.
     *
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
     * @return mixed
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
    }

    /**
     * Convert posted parameters into what the form needs in order to successfully bind.
     *
     * @param mixed[] $parameters
     * @param object  $entity
     * @param string  $action
     *
     * @return mixed
     */
    protected function prepareParametersForBinding(Request $request, $parameters, $entity, $action)
    {
        return $parameters;
    }

    protected function processBatchForm(Request $request, $key, $entity, $params, $method, &$errors, &$entities)
    {
        $this->inBatchMode = true;
        $formResponse      = $this->processForm($request, $entity, $params, $method);
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
        } elseif (is_object($formResponse) && $formResponse::class === $entity::class) {
            // Success
            $entities[$key] = $formResponse;
        } elseif (is_array($formResponse) && isset($formResponse['code'], $formResponse['message'])) {
            // There was an error
            $errors[$key] = $formResponse;
        }

        $this->doctrine->getManager()->detach($entity);

        $this->inBatchMode = false;
    }

    /**
     * Processes API Form.
     *
     * @param array<mixed>|null $parameters
     * @param string            $method
     *
     * @return mixed
     */
    protected function processForm(Request $request, $entity, $parameters = null, $method = 'PUT')
    {
        $categoryId = null;

        if (null === $parameters) {
            // get from request
            $parameters = $request->request->all();
        }

        // Store the original parameters from the request so that callbacks can have access to them as needed
        $this->entityRequestParameters = $parameters;

        // unset the ID in the parameters if set as this will cause the form to fail
        if (isset($parameters['id'])) {
            unset($parameters['id']);
        }

        // is an entity being updated or created?
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
        $submitParams = $this->prepareParametersForBinding($request, $parameters, $entity, $action);

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

        if ($form->isSubmitted() && $form->isValid()) {
            $this->setCategory($entity, $categoryId);
            $preSaveError = $this->preSaveEntity($entity, $form, $submitParams, $action);

            if ($preSaveError instanceof Response) {
                return $preSaveError;
            }

            try {
                if ($this->dispatcher->hasListeners(ApiEvents::API_ON_ENTITY_PRE_SAVE)) {
                    $this->dispatcher->dispatch(new ApiEntityEvent($entity, $this->entityRequestParameters, $request), ApiEvents::API_ON_ENTITY_PRE_SAVE);
                }
            } catch (\Exception $e) {
                return $this->returnError($e->getMessage(), $e->getCode());
            }

            $statusCode = $this->saveEntity($entity, $statusCode);

            $headers = [];
            // return the newly created entities location if applicable
            if (in_array($statusCode, [Response::HTTP_CREATED, Response::HTTP_ACCEPTED])) {
                $route = (null !== $this->router->getRouteCollection()->get('mautic_api_'.$this->entityNameMulti.'_getone'))
                    ? 'mautic_api_'.$this->entityNameMulti.'_getone' : 'mautic_api_get'.$this->entityNameOne;
                $headers['Location'] = $this->generateUrl(
                    $route,
                    array_merge(['id' => $entity->getId()], $this->routeParams),
                    true
                );
            }

            try {
                if ($this->dispatcher->hasListeners(ApiEvents::API_ON_ENTITY_POST_SAVE)) {
                    $this->dispatcher->dispatch(new ApiEntityEvent($entity, $this->entityRequestParameters, $request), ApiEvents::API_ON_ENTITY_POST_SAVE);
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
            $formErrors     = $this->getFormErrorMessages($form);
            $formErrorCodes = $this->getFormErrorCodes($form);
            $msg            = $this->getFormErrorMessage($formErrors);

            if (!$msg) {
                $msg = $this->translator->trans('mautic.core.error.badrequest', [], 'flashes');
            }

            $responseCode = in_array(Response::HTTP_UNPROCESSABLE_ENTITY, $formErrorCodes) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_BAD_REQUEST;

            return $this->returnError($msg, $responseCode, $formErrors);
        }

        return $this->handleView($view);
    }

    protected function saveEntity($entity, int $statusCode): int
    {
        $this->model->saveEntity($entity);

        return $statusCode;
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
            $category = $this->doctrine->getManager()->find(Category::class, $categoryId);

            if (null === $category) {
                throw new \UnexpectedValueException("Category $categoryId does not exist");
            }

            $entity->setCategory($category);
        }
    }
}
