<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class FocusController extends AbstractStandardFormController
{
    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(
        private CacheProvider $cacheProvider,
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    protected function getTemplateBase(): string
    {
        return '@MauticFocus/Focus';
    }

    protected function getModelName(): string
    {
        return 'focus';
    }

    /**
     * @param int $page
     */
    public function indexAction(Request $request, $page = 1): Response
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
     * @throws \Exception
     */
    public function getViewArguments(array $args, $action): array
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

            $statsDateFrom = new \DateTime($dateRangeForm->get('date_from')->getData());
            $statsDateTo   = new \DateTime($dateRangeForm->get('date_to')->getData());
            $cacheKey      = "focus.viewArguments.{$item->getId()}.{$statsDateFrom->getTimestamp()}.{$statsDateTo->getTimestamp()}";
            $cacheItem     = $this->cacheProvider->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                [$stats, $trackables] = $cacheItem->get();
            } else {
                // invalidate cache for entire focus item to keep AJAX loaded data consistent
                $this->cacheProvider->invalidateTags(["focus.{$item->getId()}"]);

                /** @var FocusModel $model */
                $model = $this->getModel('focus');
                $stats = $model->getStats(
                    $item,
                    null,
                    $statsDateFrom,
                    $statsDateTo
                );

                if ('link' === $item->getType()) {
                    $trackableModel = $this->getModel('page.trackable');
                    \assert($trackableModel instanceof TrackableModel);
                    $trackables = $trackableModel->getTrackableList('focus', $item->getId());

                    $cacheItem->set([$stats, $trackables]);
                    $cacheItem->expiresAfter($cacheTimeout * 60);
                    $cacheItem->tag("focus.{$item->getId()}");
                    $this->cacheProvider->save($cacheItem);
                }
            }

            $args['viewParameters']['stats']                 = $stats;
            $args['viewParameters']['dateRangeForm']         = $dateRangeForm->createView();
            $args['viewParameters']['showConversionRate']    = true;
            if (isset($trackables)) {
                $args['viewParameters']['trackables'] = $trackables;
            }
        }

        return $args;
    }

    /**
     * @return mixed[]
     */
    protected function getPostActionRedirectArguments(array $args, $action): array
    {
        $focus        = $this->getCurrentRequest()->request->all()['focus'] ?? [];
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
        $focus        = $this->getCurrentRequest()->request->all()['focus'] ?? [];
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
     */
    protected function getUpdateSelectParams($updateSelect, $entity, $nameMethod = 'getName', $groupMethod = 'getLanguage'): array
    {
        return [
            'updateSelect' => $updateSelect,
            'id'           => $entity->getId(),
            'name'         => $entity->$nameMethod(),
        ];
    }
}
