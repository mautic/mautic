<?php

namespace Mautic\DynamicContentBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\TranslationModelTrait;
use Mautic\CoreBundle\Model\VariantModelTrait;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Entity\Stat;
use Mautic\DynamicContentBundle\Event\DynamicContentEvent;
use Mautic\DynamicContentBundle\Form\Type\DynamicContentType;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<DynamicContent>
 *
 * @implements AjaxLookupModelInterface<DynamicContent>
 */
class DynamicContentModel extends FormModel implements AjaxLookupModelInterface
{
    use VariantModelTrait;
    use TranslationModelTrait;

    /**
     * Retrieve the permissions base.
     */
    public function getPermissionBase(): string
    {
        return 'dynamiccontent:dynamiccontents';
    }

    /**
     * @return DynamicContentRepository
     */
    public function getRepository()
    {
        /** @var DynamicContentRepository $repo */
        $repo = $this->em->getRepository(\Mautic\DynamicContentBundle\Entity\DynamicContent::class);

        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * @return \Mautic\DynamicContentBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        return $this->em->getRepository(\Mautic\DynamicContentBundle\Entity\Stat::class);
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

    /**
     * @param string|null $action
     * @param array       $options
     *
     * @throws \InvalidArgumentException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof DynamicContent) {
            throw new \InvalidArgumentException('Entity must be of class DynamicContent');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(DynamicContentType::class, $entity, $options);
    }

    public function setSlotContentForLead(DynamicContent $dwc, Lead $lead, $slot): void
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        $qb->insert(MAUTIC_TABLE_PREFIX.'dynamic_content_lead_data')
            ->values([
                'lead_id'            => $lead->getId(),
                'dynamic_content_id' => $dwc->getId(),
                'slot'               => ':slot',
                'date_added'         => $qb->expr()->literal((new \DateTime())->format('Y-m-d H:i:s')),
            ])->setParameter('slot', $slot);

        $qb->executeStatement();
    }

    /**
     * @param string     $slot
     * @param Lead|array $lead
     *
     * @return array<string, mixed>|false
     */
    public function getSlotContentForLead($slot, $lead)
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
    public function createStatEntry(DynamicContent $dynamicContent, $lead, $source = null): void
    {
        if (empty($lead)) {
            return;
        }

        if ($lead instanceof Lead && !$lead->getId()) {
            return;
        }

        if (is_array($lead)) {
            if (empty($lead['id'])) {
                return;
            }

            $lead = $this->em->getReference(\Mautic\LeadBundle\Entity\Lead::class, $lead['id']);
        }

        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setDynamicContent($dynamicContent);
        $stat->setSource($source);

        $this->getStatRepository()->saveEntity($stat);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
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
            if (empty($event)) {
                $event = new DynamicContentEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
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
     * @param char   $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     * @param bool   $canViewOthers
     */
    public function getHitsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
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
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     *
     * @return mixed[]
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = []): array
    {
        $results = [];
        switch ($type) {
            case 'dynamicContent':
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

                break;
        }

        return $results;
    }
}
