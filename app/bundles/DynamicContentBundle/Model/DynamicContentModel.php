<?php

namespace Mautic\DynamicContentBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Entity\Stat;
use Mautic\DynamicContentBundle\Entity\StatRepository;
use Mautic\DynamicContentBundle\Event\DynamicContentEvent;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentType;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<DynamicContent>
 *
 * @implements AjaxLookupModelInterface<DynamicContent>
 */
class DynamicContentModel extends FormModel implements AjaxLookupModelInterface, GlobalSearchInterface
{
    use VariantModelTrait;
    use TranslationModelTrait;

    public static function getName(): string
    {
        return 'dynamicContent.dynamicContent';
    }

    private StatRepository $statRepository;

    private DynamicContentRepository $dynamicContentRepository;

    #[Required]
    public function autowireDynamicContentModel(
        DynamicContentRepository $dynamicContentRepository,
        StatRepository $statRepository,
    ): void {
        $this->dynamicContentRepository = $dynamicContentRepository;
        $this->statRepository = $statRepository;
    }

    /**
     * Retrieve the permissions base.
     */
    public function getPermissionBase(): string
    {
        return 'dynamiccontent:dynamiccontents';
    }

    public function getRepository(): DynamicContentRepository
    {
        $this->dynamicContentRepository->setCurrentUser($this->userHelper->getUser());

        return $this->dynamicContentRepository;
    }

    public function getStatRepository(): StatRepository
    {
        return $this->statRepository;
    }

    /**
     * @param object $entity
     * @param bool   $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        parent::saveEntity($entity, $unlock);

        $this->postTranslationEntitySave($entity);
    }

    public function getEntity($id = null): ?DynamicContent
    {
        if (null === $id) {
            return new DynamicContent();
        }

        return parent::getEntity($id);
    }

    public function checkEntityBySlotName(string $slotName, ?string $type = null, string $typeCondition = '=',
        ?int $skipId = null): bool
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        $qb->select('1')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->where($qb->expr()->eq('slot_name', ':slot_name'))
            ->setParameter('slot_name', $slotName)
            ->setMaxResults(1);

        if (!empty($type)) {
            if (!in_array($typeCondition, ['=', '<>', '!='], true)) {
                throw new \InvalidArgumentException("Invalid operator '{$typeCondition}'");
            }

            $qb->andWhere("type {$typeCondition} :type");
            $qb->setParameter('type', $type);
        }

        if (null !== $skipId) {
            $qb->andWhere('id != :id');
            $qb->setParameter('id', $skipId);
        }

        return (bool) $qb->executeQuery()->fetchOne();
    }

    /**
     * @param string|null $action
     * @param array       $options
     *
     * @throws \InvalidArgumentException
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof DynamicContent) {
            throw new \InvalidArgumentException('Entity must be of class DynamicContent');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(DynamicContentType::class, $entity, $options);
    }

    public function setSlotContentForLead(DynamicContent $dwc, Lead $lead, $slot): void
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        $qb->insert(MAUTIC_TABLE_PREFIX.'dynamic_content_lead_data')
            ->values([
                'lead_id'            => $lead->getId(),
                'dynamic_content_id' => $dwc->getId(),
                'slot'               => ':slot',
                'date_added'         => $qb->expr()->literal(new \DateTime()->format('Y-m-d H:i:s')),
            ])->setParameter('slot', $slot);

        $qb->executeStatement();
    }

    /**
     * @param Lead|mixed[]|null $lead
     *
     * @return array<string, mixed>|false
     */
    public function getSlotContentForLead(string $slot, array|Lead|null $lead)
    {
        if (!$lead) {
            return [];
        }

        $qb = $this->em->getConnection()->createQueryBuilder();

        $id = $lead instanceof Lead ? $lead->getId() : $lead['id'];

        $qb->select('dc.id, dc.content')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content', 'dc')
            ->leftJoin('dc', MAUTIC_TABLE_PREFIX.'dynamic_content_lead_data', 'dcld', 'dcld.dynamic_content_id = dc.id')
            ->andWhere($qb->expr()->eq('dcld.slot', ':slot'))
            ->andWhere($qb->expr()->eq('dcld.lead_id', ':lead_id'))
            ->andWhere($qb->expr()->eq('dc.is_published', 1))
            ->setParameter('slot', $slot)
            ->setParameter('lead_id', $id)
            ->orderBy('dcld.date_added', 'DESC')
            ->addOrderBy('dcld.id', 'DESC');

        return $qb->executeQuery()->fetchAssociative();
    }

    /**
     * @param Lead|array $lead
     * @param string     $source
     */
    public function createStatEntry(DynamicContent $dynamicContent, $lead, $source = null): ?Stat
    {
        if (empty($lead)) {
            return null;
        }

        if ($lead instanceof Lead && !$lead->getId()) {
            return null;
        }

        if (is_array($lead)) {
            if (empty($lead['id'])) {
                return null;
            }

            $lead = $this->em->getReference(Lead::class, $lead['id']);
        }

        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setDynamicContent($dynamicContent);
        $stat->setSource($source);

        $this->statRepository->saveEntity($stat);

        return $stat;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof DynamicContent) {
            throw new MethodNotAllowedHttpException(['Dynamic Content']);
        }

        switch ($action) {
            case 'pre_save':
                $name = DynamicContentEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = DynamicContentEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = DynamicContentEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = DynamicContentEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof Event) {
                $event = new DynamicContentEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    /**
     * Joins the page table and limits created_by to currently logged in user.
     */
    public function limitQueryToCreator(QueryBuilder &$q): void
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'dynamic_content', 'd', 'd.id = t.dynamic_content_id')
            ->andWhere('d.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * Get line chart data of hits.
     *
     * @param ?string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string  $dateFormat
     * @param bool    $canViewOthers
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, array $filter = [], $canViewOthers = true): array
    {
        $flag = null;

        if (isset($filter['flag'])) {
            $flag = $filter['flag'];
            unset($filter['flag']);
        }

        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$flag || 'total_and_unique' === $flag) {
            $q = $query->prepareTimeDataQuery('dynamic_content_stats', 'date_sent', $filter);

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.dynamicContent.show.total.views'), $data);
        }

        if ('unique' === $flag || 'total_and_unique' === $flag) {
            $q = $query->prepareTimeDataQuery('dynamic_content_stats', 'date_sent', $filter);
            $q->groupBy('t.lead_id, t.date_sent');

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            $chart->setDataset($this->translator->trans('mautic.dynamicContent.show.unique.views'), $data);
        }

        return $chart->render();
    }

    /**
     * @param string|array<int, string> $filter
     * @param array<string, mixed>      $options
     *
     * @return mixed[]
     */
    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        $results = [];
        if ('dynamicContent' === $type) {
            $entities = $this->getRepository()->getDynamicContentList(
                $filter,
                $limit,
                $start,
                $this->security->isGranted($this->getPermissionBase().':viewother'),
                $options['top_level'] ?? false,
                $options['ignore_ids'] ?? [],
                $options['where'] ?? ''
            );
            foreach ($entities as $entity) {
                $results[$entity['language']][$entity['id']] = $entity['name'];
            }
            // sort by language
            ksort($results);
        }

        return $results;
    }
}
