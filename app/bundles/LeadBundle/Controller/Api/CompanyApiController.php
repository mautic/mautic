<?php

namespace Mautic\LeadBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Company>
 */
class CompanyApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use LeadAccessTrait;

    /**
     * @var CompanyModel|null
     */
    protected $model;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine, ModelFactory $modelFactory, EventDispatcherInterface $dispatcher, CoreParametersHelper $coreParametersHelper)
    {
        $companyModel = $modelFactory->getModel('lead.company');
        \assert($companyModel instanceof CompanyModel);

        $this->model              = $companyModel;
        $this->entityClass        = Company::class;
        $this->entityNameOne      = 'company';
        $this->entityNameMulti    = 'companies';
        $this->serializerGroups[] = 'companyDetails';
        $this->serializerGroups[] = 'tagList';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    public function getNewEntity(array $params)
    {
        $leadCompanyModel = $this->getModel('lead.company');
        \assert($leadCompanyModel instanceof CompanyModel);
        [$company, $companyEntities] = IdentifyCompanyHelper::findCompany($params, $leadCompanyModel);
        if (count($companyEntities)) {
            return $this->model->getEntity($company['id']);
        }

        return $this->model->getEntity();
    }

    protected function prepareParametersForBinding(Request $request, $parameters, $entity, $action)
    {
        if (isset($parameters['tags'])) {
            unset($parameters['tags']);
        }

        foreach ($entity->getTags() as $tag) {
            $parameters['tags'][] = $tag->getId();
        }

        return $parameters;
    }

    /**
     * @param Company &$entity
     * @param string  $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($this->entityRequestParameters['tags'])) {
            $this->model->modifyTags($entity, $this->entityRequestParameters['tags'], null, false);
        }

        $this->setCustomFieldValues($entity, $form, $parameters);
    }

    /**
     * Adds a contact to a company.
     *
     * @param int $companyId Company ID
     * @param int $contactId Contact ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Response::HTTP_OK);

        if (null === $company) {
            return $this->notFound();
        }

        $contact = $this->checkLeadAccess($contactId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        $this->model->addLeadToCompany($company, $contact);

        return $this->handleView($view);
    }

    /**
     * Removes given contact from a company.
     *
     * @param int $companyId List ID
     * @param int $contactId Lead ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeContactAction($companyId, $contactId)
    {
        $company = $this->model->getEntity($companyId);
        $view    = $this->view(['success' => 1], Response::HTTP_OK);

        if (null === $company) {
            return $this->notFound();
        }

        $contactModel = $this->getModel('lead');
        $contact      = $contactModel->getEntity($contactId);

        // Does the contact exist and the user has permission to edit
        if (null === $contact) {
            return $this->notFound();
        } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
            return $this->accessDenied();
        }

        $this->model->removeLeadFromCompany($company, $contact);

        return $this->handleView($view);
    }

    /**
     * Adds tags to a company.
     *
     * @return Response
     */
    public function addTagsAction(Request $request, $companyId)
    {
        $requestParameters = $request->request->all();
        $tags              = $requestParameters['tags'] ?? [];
        if (!is_array($tags) && !is_string($tags)) {
            $tags = [];
        }

        return $this->applyTagsAction((int) $companyId, 'modifyTags', $tags);
    }

    /**
     * Removes a tag from a company.
     *
     * @return Response
     */
    public function removeTagAction($companyId, $tagId)
    {
        return $this->applyTagsAction((int) $companyId, 'removeTag', (int) $tagId);
    }

    /**
     * Add/Remove tags to/from a company.
     *
     * @return Response
     */
    protected function applyTagsAction(int $companyId, string $method, mixed $data)
    {
        $company = $this->model->getEntity($companyId);
        if (null === $company) {
            return $this->notFound();
        }

        if (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $company->getPermissionUser())) {
            return $this->accessDenied();
        }

        $result = $this->model->$method($company, $data);
        if (false === $result) {
            return $this->badRequest();
        }

        if ('removeTag' === $method) {
            $view = $this->view(
                [
                    'recordFound'        => $result,
                    $this->entityNameOne => $company,
                ],
                Response::HTTP_OK
            );
        } else {
            $view = $this->view([$this->entityNameOne => $company], Response::HTTP_OK);
        }

        return $this->handleView($view);
    }
}
