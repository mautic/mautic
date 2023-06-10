<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FocusController.
 */
class FocusController extends AbstractStandardFormController
{
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
     */
    public function indexAction(Request $request, $page = 1): JsonResponse|RedirectResponse|Response
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * Generates new form and processes post data.
     */
    public function newAction(Request $request): JsonResponse|Response
    {
        return parent::newStandard($request);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     */
    public function editAction(Request $request, $objectId, $ignorePost = false): JsonResponse|Response
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Displays details on a Focus.
     */
    public function viewAction(Request $request, $objectId): array|JsonResponse|RedirectResponse|Response
    {
        return parent::viewStandard($request, $objectId, 'focus', 'plugin.focus');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): JsonResponse|RedirectResponse|Response
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): JsonResponse|RedirectResponse
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): JsonResponse|RedirectResponse
    {
        return parent::batchDeleteStandard($request);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getViewArguments(array $args, $action)
    {
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

            $args['viewParameters']['stats']         = $stats;
            $args['viewParameters']['dateRangeForm'] = $dateRangeForm->createView();

            if ('link' === $item->getType()) {
                $trackableModel = $this->getModel('page.trackable');
                \assert($trackableModel instanceof TrackableModel);
                $args['viewParameters']['trackables'] = $trackableModel->getTrackableList('focus', $item->getId());
            }
        }

        return $args;
    }

    /**
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
