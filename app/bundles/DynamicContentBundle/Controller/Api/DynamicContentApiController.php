<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<DynamicContent>
 */
final class DynamicContentApiController extends CommonApiController
{
    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        DynamicContentModel $dynamicContentModel,
    ) {
        $this->model            = $dynamicContentModel;
        $this->entityClass      = DynamicContent::class;
        $this->entityNameOne    = 'dynamicContent';
        $this->entityNameMulti  = 'dynamicContents';
        $this->serializerGroups = ['dwcDetails', 'categoryList'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @return Response
     */
    public function newEntityAction(Request $request)
    {
        $parameters = $request->request->all();

        /** @var DynamicContent $entity */
        $entity     = $this->getNewEntity($parameters);

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        $entity->setSlotName($parameters['slotName'] ?? null);

        if (isset($parameters['type'])) {
            $entity->setType($parameters['type']);
        }

        $entity->setIsCampaignBased($parameters['isCampaignBased'] ?? false);

        return $this->processForm($request, $entity, $parameters, 'POST');
    }

    /**
     * @return Response
     */
    public function editEntityAction(Request $request, $id)
    {
        /** @var DynamicContent|null $entity */
        $entity     = $this->model->getEntity($id);
        $parameters = $request->request->all();
        $method     = $request->getMethod();

        if (null === $entity || !$entity->getId()) {
            if ('PATCH' === $method) {
                // PATCH requires that an entity exists
                return $this->notFound();
            }

            // PUT can create a new entity if it doesn't exist
            /** @var DynamicContent $entity */
            $entity = $this->model->getEntity();
            $entity->setSlotName($parameters['slotName'] ?? null);
            $entity->setIsCampaignBased($parameters['isCampaignBased'] ?? false);

            if (!$this->checkEntityAccess($entity, 'create')) {
                return $this->accessDenied();
            }
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        $entity->setSlotName($parameters['slotName'] ?? null);
        $entity->setIsCampaignBased($parameters['isCampaignBased'] ?? false);

        return $this->processForm($request, $entity, $parameters, $method);
    }
}
