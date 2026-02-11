<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
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
     * @var array<string, mixed>
     */
    protected array $listFilters = [];

    /**
     * @phpstan-ignore-next-line
     */
    public function __construct(
        private CacheProviderTagAwareInterface $cacheProvider,
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
     * @param mixed   $start
     * @param mixed   $limit
     * @param mixed   $filter
     * @param mixed   $orderBy
     * @param mixed   $orderByDir
     * @param mixed[] $args
     *
     * @return array{0: int, 1: array<int, mixed>}
     */
    protected function getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, array $args = [])
    {
        /** @var \Mautic\CategoryBundle\Model\CategoryModel $categoryModel */
        $categoryModel        = $this->getModel('category');
        $categories           = $categoryModel->getLookupResults('plugin:focus', '', 0);
        $categoryFilterPrefix = $this->translator->trans('mautic.core.searchcommand.category');

        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.core.category.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.categories' => [
                        'options' => $categories,
                        'prefix'  => $categoryFilterPrefix,
                    ],
                ],
            ],
        ];

        $request        = $this->getCurrentRequest();
        $session        = $request->getSession();
        $currentFilters = $session->get('mautic.'.$this->getSessionBase().'.list_filters', []);
        $updatedFilters = $request->get('filters', false);

        if ($updatedFilters) {
            $newFilters     = [];
            $updatedFilters = json_decode($updatedFilters, true);

            if ($updatedFilters) {
                foreach ($updatedFilters as $updatedFilter) {
                    [$clmn, $fltr]       = explode(':', $updatedFilter);
                    $newFilters[$clmn][] = $fltr;
                }

                $currentFilters = $newFilters;
            } else {
                $currentFilters = [];
            }
        }
        $session->set('mautic.'.$this->getSessionBase().'.list_filters', $currentFilters);

        if (!empty($currentFilters)) {
            $categoryIdsByAlias = [];
            foreach ($categories as $category) {
                if (!empty($category['alias'])) {
                    $categoryIdsByAlias[$category['alias']] = (int) $category['id'];
                }
            }

            $catIds = [];
            foreach ($currentFilters as $type => $typeFilters) {
                if ($type === $categoryFilterPrefix) {
                    $type = 'category';
                }

                if ('category' !== $type) {
                    continue;
                }

                $listFilters['filters']['groups']['mautic.core.filter.categories']['values'] = $typeFilters;

                foreach ($typeFilters as $fltr) {
                    if (is_numeric($fltr)) {
                        $catIds[] = (int) $fltr;
                        continue;
                    }

                    if (isset($categoryIdsByAlias[$fltr])) {
                        $catIds[] = $categoryIdsByAlias[$fltr];
                    }
                }
            }

            if (!empty($catIds)) {
                $filter['force'][] = ['column' => 'c.id', 'expr' => 'in', 'value' => array_values(array_unique($catIds))];
            }
        }

        $this->listFilters = $listFilters;

        return parent::getIndexItems($start, $limit, $filter, $orderBy, $orderByDir, $args);
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
        return parent::viewStandard($request, $objectId, 'focus', 'focus');
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
        if ('index' === $action) {
            $args['viewParameters']['filters'] = $this->listFilters;
        }

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
