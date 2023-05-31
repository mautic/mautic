<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FocusController.
 */
class FocusController extends AbstractStandardFormController
{
    private CacheProvider $cacheProvider;

    public function __construct(
        CorePermissions $security,
        UserHelper $userHelper,
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        ManagerRegistry $managerRegistry,
        CacheProvider $cacheProvider,
    ) {
        $this->cacheProvider = $cacheProvider;

        parent::__construct($security, $userHelper, $formFactory, $fieldHelper, $managerRegistry);
    }

    protected function getTemplateBase(): string
    {
        return '@MauticFocus/Focus';
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'focus';
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function indexAction(Request $request, $page = 1)
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return JsonResponse|Response
     */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Displays details on a Focus.
     *
     * @param $objectId
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function viewAction(Request $request, $objectId)
    {
        return parent::viewStandard($request, $objectId, 'focus', 'plugin.focus');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function cloneAction(Request $request, $objectId)
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return parent::batchDeleteStandard($request);
    }

    /**
     * @param $action
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getViewArguments(array $args, $action)
    {
        $cacheTimeout = (int) $this->coreParametersHelper->get('cached_data_timeout');

        if ('view' == $action) {
            /** @var Focus $item */
            $item = $args['viewParameters']['item'];

            // For line graphs in the view
            $dateRangeValues = $this->getCurrentRequest()->get('daterange', []);
            $dateRangeForm   = $this->formFactory->create(
                DateRangeType::class,
                $dateRangeValues,
                [
                    'action' => $this->generateUrl(
                        'mautic_focus_action',
                        [
                            'objectAction' => 'view',
                            'objectId'     => $item->getId(),
                        ]
                    ),
                ]
            );

            /** @var FocusModel $model */
            $model = $this->getModel('focus');
            $stats = $model->getStats(
                $item,
                null,
                new \DateTime($dateRangeForm->get('date_from')->getData()),
                new \DateTime($dateRangeForm->get('date_to')->getData())
            );

            $args['viewParameters']['stats']                 = $stats;
            $args['viewParameters']['dateRangeForm']         = $dateRangeForm->createView();
            $args['viewParameters']['showConversionRate']    = true;

            if ('link' === $item->getType()) {
                $cacheItem    = $this->cacheProvider->getItem('focus.trackables.'.$item->getId());
                if ($cacheItem->isHit()) {
                    $trackableList = $cacheItem->get();
                } else {
                    $trackableModel = $this->getModel('page.trackable');
                    \assert($trackableModel instanceof TrackableModel);
                    $trackableList = $trackableModel->getTrackableList('focus', $item->getId());
                    $cacheItem->set($trackableList);
                    $cacheItem->expiresAfter($cacheTimeout * 60);
                    $this->cacheProvider->save($cacheItem);
                }
                $args['viewParameters']['trackables'] = $trackableList;
            }
        }

        return $args;
    }

    /**
     * @param $action
     *
     * @return array
     */
    protected function getPostActionRedirectArguments(array $args, $action)
    {
        $focus        = $this->getCurrentRequest()->request->get('focus') ?? [];
        $updateSelect = 'POST' === $this->getCurrentRequest()->getMethod()
            ? ($focus['updateSelect'] ?? false)
            : $this->getCurrentRequest()->get('updateSelect', false);

        if ($updateSelect) {
            switch ($action) {
                case 'new':
                case 'edit':
                    $passthrough = $args['passthroughVars'];
                    $passthrough = array_merge(
                        $passthrough,
                        [
                            'updateSelect' => $updateSelect,
                            'id'           => $args['entity']->getId(),
                            'name'         => $args['entity']->getName(),
                        ]
                    );
                    $args['passthroughVars'] = $passthrough;
                    break;
            }
        }

        return $args;
    }

    /**
     * @return array
     */
    protected function getEntityFormOptions()
    {
        $focus        = $this->getCurrentRequest()->request->get('focus') ?? [];
        $updateSelect = 'POST' === $this->getCurrentRequest()->getMethod()
            ? ($focus['updateSelect'] ?? false)
            : $this->getCurrentRequest()->get('updateSelect', false);

        if ($updateSelect) {
            return ['update_select' => $updateSelect];
        }
    }

    /**
     * Return array of options update select response.
     *
     * @param string $updateSelect HTML id of the select
     * @param object $entity
     * @param string $nameMethod   name of the entity method holding the name
     * @param string $groupMethod  name of the entity method holding the select group
     *
     * @return array
     */
    protected function getUpdateSelectParams($updateSelect, $entity, $nameMethod = 'getName', $groupMethod = 'getLanguage')
    {
        return [
            'updateSelect' => $updateSelect,
            'id'           => $entity->getId(),
            'name'         => $entity->$nameMethod(),
        ];
    }
}
