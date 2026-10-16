<?php

namespace Mautic\StageBundle\Model;

use Doctrine\DBAL\ParameterType;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\LeadBundle\Entity\StagesChangeLogRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\LeadStageLogRepository;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\StageBundle\Event\StageBuilderEvent;
use Mautic\StageBundle\Event\StageEvent;
use Mautic\StageBundle\Form\Type\StageType;
use Mautic\StageBundle\StageEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends CommonFormModel<Stage>
 */
class StageModel extends CommonFormModel implements GlobalSearchInterface
{
    protected LeadModel $leadModel;

    private StageRepository $stageRepository;

    private StagesChangeLogRepository $stagesChangeLogRepository;

    private LeadStageLogRepository $leadStageLogRepository;

    #[Required]
    public function autowireStageModel(
        LeadModel $leadModel,
        StageRepository $stageRepository,
        StagesChangeLogRepository $stagesChangeLogRepository,
        LeadStageLogRepository $leadStageLogRepository,
    ): void {
        $this->leadModel                 = $leadModel;
        $this->stageRepository           = $stageRepository;
        $this->stagesChangeLogRepository = $stagesChangeLogRepository;
        $this->leadStageLogRepository    = $leadStageLogRepository;
    }

    public function getRepository(): StageRepository
    {
        return $this->stageRepository;
    }

    public function getPermissionBase(): string
    {
        return 'stage:stages';
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Stage) {
            throw new MethodNotAllowedHttpException(['Stage']);
        }
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(StageType::class, $entity, $options);
    }

    public function getEntity($id = null): ?Stage
    {
        if (null === $id) {
            return new Stage();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Stage) {
            throw new MethodNotAllowedHttpException(['Stage']);
        }

        switch ($action) {
            case 'pre_save':
                $name = StageEvents::STAGE_PRE_SAVE;
                break;
            case 'post_save':
                $name = StageEvents::STAGE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = StageEvents::STAGE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = StageEvents::STAGE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new StageEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed StageEvents::STAGE_ON_BUILD.
     *
     * @return mixed
     */
    public function getStageActions()
    {
        static $actions;

        if (empty($actions)) {
            // build them
            $actions = [];
            $event   = new StageBuilderEvent($this->translator);
            $this->dispatcher->dispatch($event, StageEvents::STAGE_ON_BUILD);
            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }

    /**
     * Get line chart data of stages.
     *
     * @param ?string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string  $dateFormat
     * @param array   $filter
     * @param bool    $canViewOthers
     */
    public function getStageLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('lead_stages_change_log', 'date_added', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
                ->andWhere('l.owner_id = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.stage.changes'), $data);

        return $chart->render();
    }

    public function stageMerge(Stage $primaryStage, Stage $secondaryStage): Stage
    {
        $this->logger->debug('STAGE: Merging stages');

        $primaryStageId   = $primaryStage->getId();
        $secondaryStageId = $secondaryStage->getId();

        if ($primaryStageId === $secondaryStageId) {
            return $primaryStage;
        }

        $this->em->wrapInTransaction(function () use ($primaryStageId, $secondaryStage, $secondaryStageId): void {
            $this->em->getConnection()->createQueryBuilder()
                ->update(MAUTIC_TABLE_PREFIX.'leads')
                ->set('stage_id', ':primaryStageId')
                ->where('stage_id = :secondaryStageId')
                ->setParameter('primaryStageId', $primaryStageId, ParameterType::INTEGER)
                ->setParameter('secondaryStageId', $secondaryStageId, ParameterType::INTEGER)
                ->executeStatement();

            $this->stagesChangeLogRepository->updateStage($secondaryStageId, $primaryStageId);

            $this->leadStageLogRepository->updateStage($secondaryStageId, $primaryStageId);

            $this->deleteEntity($secondaryStage);
        });

        return $primaryStage;
    }

    public function getUserStages(): array
    {
        $user = (!$this->security->isGranted('stage:stages:viewother')) ?
            $this->userHelper->getUser() : false;

        return $this->stageRepository->getStages($user);
    }

    /**
     * Fetch stage entities together with per-stage contact counts.
     *
     * Returns a tuple: Stage objects for the current page, a map of
     * stage ID to contact count, and the total item count across all pages.
     *
     * @param array<string, mixed> $args
     *
     * @return array{0: array<int, Stage>, 1: array<int, int>, 2: int}
     */
    public function getEntitiesWithContactCounts(array $args = []): array
    {
        $args['withContactCount'] = true;
        $paginator                = $this->getEntities($args);

        $entities      = [];
        $contactCounts = [];

        foreach ($paginator as $row) {
            /** @var Stage $stage */
            $stage                          = is_array($row) ? $row[0] : $row;
            $entities[]                     = $stage;
            $contactCounts[$stage->getId()] = is_array($row) && isset($row['contactCount'])
                ? (int) $row['contactCount']
                : 0;
        }

        return [$entities, $contactCounts, count($paginator)];
    }
}
