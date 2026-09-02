<?php

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<DynamicContent>
 */
final class DynamicContentRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    public const SEARCH = ':search';

    /**
     * Get a list of entities.
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->_em
            ->createQueryBuilder()
            ->select('e')
            ->from(DynamicContent::class, 'e', 'e.id');

        if (empty($args['iterable_mode'])) {
            $q->leftJoin('e.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr = $q->expr()->or(
                    $q->expr()->eq('e.language', ":{$unique}"),
                    $q->expr()->like('e.language', ":{$langUnique}")
                );
                break;
            case $this->translator->trans('mautic.project.searchcommand.name'):
            case $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US'):
                return $this->handleProjectFilter(
                    $this->_em->getConnection()->createQueryBuilder(),
                    'dynamic_content_id',
                    'dynamic_content_projects_xref',
                    $this->getTableAlias(),
                    $filter->string,
                    $filter->not
                );
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        }

        return [$expr, $parameters];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.lang',
            'mautic.project.searchcommand.name',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['e.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * Up the sent counts.
     *
     * @param int $increaseBy
     */
    public function upSentCount($id, $increaseBy = 1): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->set('sent_count', 'sent_count + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        $q->executeStatement();
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param bool   $viewOther
     * @param bool   $topLevel
     * @param array  $ignoreIds
     * @param string $where
     *
     * @return array
     */
    public function getDynamicContentList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevel = false, $ignoreIds = [], $where = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, name, language}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map(intval(...), $search);
                $q->andWhere($q->expr()->in('e.id', self::SEARCH))
                  ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like('e.name', self::SEARCH))
                  ->setParameter('search', "%{$search}%");
            }
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('e.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ('translation' == $topLevel) {
            // only get top level pages
            $q->andWhere($q->expr()->isNull('e.translationParent'));
        } elseif ('variant' == $topLevel) {
            $q->andWhere($q->expr()->isNull('e.variantParent'));
        }

        if (!empty($ignoreIds)) {
            $q->andWhere($q->expr()->notIn('e.id', ':dwc_ids'))
                ->setParameter('dwc_ids', $ignoreIds);
        }

        if ($where) {
            $q->andWhere($where);
        }

        $q->orderBy('e.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @return array<mixed>
     */
    public function getSlotNamesList(string $search = '', int $limit = 10, int $start = 0): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->select('distinct slot_name')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->where('is_published = :true')
            ->setParameter('true', true, 'boolean');

        if (!empty($search)) {
            $qb->andWhere($qb->expr()->like('slot_name', self::SEARCH))
                ->setParameter('search', "{$search}%");
        }

        if (!empty($limit)) {
            $qb->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function getDynamicContentForSlotFromCampaign($slot): DynamicContent|false
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        $qb->select('ce.properties')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events', 'ce')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = ce.campaign_id')
            ->andWhere($qb->expr()->eq('ce.type', $qb->expr()->literal('dwc.decision')))
            ->andWhere($qb->expr()->like('ce.properties', ':slot'))
            ->setParameter('slot', '%'.$slot.'%')
            ->orderBy('c.is_published');

        $result = $qb->executeQuery()->fetchAllAssociative();

        foreach ($result as $item) {
            $properties = Serializer::decode($item['properties']);

            if (isset($properties['dynamicContent'])) {
                $dwc = $this->getEntity($properties['dynamicContent']);

                if ($dwc instanceof DynamicContent) {
                    return $dwc;
                }
            }
        }

        return false;
    }

    /**
     * @return array<mixed>
     */
    public function getDynamicContentBySlotName(string $slotName): array
    {
        return $this->_em->getConnection()->createQueryBuilder()
            ->select('id, name, display_order', 'content')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->where('slot_name = :slot_name')
            ->andWhere('is_campaign_based = :false')
            ->orderBy('display_order')
            ->setParameter('slot_name', $slotName)
            ->setParameter('false', false, 'boolean')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function reorderDwc(int $currentOrder, int $newOrder, string $slotName): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        if ($currentOrder < $newOrder) {
            $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content', 'd')
                ->set('d.display_order', 'd.display_order - 1')
                ->where('d.display_order > :currentOrder')
                ->andWhere('d.display_order < :newOrder')
                ->andWhere('d.slot_name = :slotName');
        } else {
            $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content', 'd')
                ->set('d.display_order', 'd.display_order + 1')
                ->where('d.display_order >= :newOrder')
                ->andWhere('d.display_order < :currentOrder')
                ->andWhere('d.slot_name = :slotName');
        }

        $q->setParameter('currentOrder', $currentOrder)
            ->setParameter('newOrder', $newOrder)
            ->setParameter('slotName', $slotName)
            ->executeQuery();
    }

    public function getLastDisplayOrder(string $slotName): int
    {
        return (int) $this->_em->getConnection()->createQueryBuilder()
            ->select('MAX(display_order) as last_order')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content')
            ->where('slot_name = :slot_name')
            ->setParameter('slot_name', $slotName)
            ->executeQuery()
            ->fetchOne();
    }
}
