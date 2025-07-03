<?php

namespace Mautic\ChannelBundle\Entity;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Message>
 */
class MessageRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities(array $args = [])
    {
        $qb = $this->createQueryBuilder($this->getTableAlias());
        $qb->join(Channel::class, 'channel', 'WITH', 'channel.message = '.$this->getTableAlias().'.id');
        $qb->leftJoin(Category::class, 'cat', 'WITH', 'cat.id = '.$this->getTableAlias().'.category');
        $qb->groupBy($this->getTableAlias().'.id');

        $args['qb'] = $qb;

        return parent::getEntities($args);
    }

    public function getTableAlias(): string
    {
        return 'm';
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getMessageList($search = '', $limit = 10, $start = 0)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($this->getTableAlias());
        $q->select('partial '.$alias.'.{id, name, description}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $q->andWhere($q->expr()->in($alias.'.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like($alias.'.name', ':search'))
                    ->setParameter('search', "%{$search}%");
            }
        }
        $q->andWhere($q->expr()->eq($alias.'.isPublished', true));

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    public function getMessageChannels($messageId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'message_channels', 'mc')
            ->select('id, channel, channel_id, properties')
            ->where($q->expr()->eq('message_id', ':messageId'))
            ->setParameter('messageId', $messageId)
            ->andWhere($q->expr()->eq('is_enabled', true));

        $results = $q->executeQuery()->fetchAllAssociative();

        $channels = [];
        foreach ($results as $result) {
            $result['properties']         = json_decode($result['properties'], true);
            $channels[$result['channel']] = $result;
        }

        return $channels;
    }

    /**
     * @return array
     */
    public function getChannelMessageByChannelId($channelId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'message_channels', 'mc')
            ->select('id, channel, channel_id, properties, message_id')
            ->where($q->expr()->eq('id', ':channelId'))
            ->setParameter('channelId', $channelId)
            ->andWhere($q->expr()->eq('is_enabled', true));

        return $q->executeQuery()->fetchAssociative();
    }

    /**
     * @param object $filter
     *
     * @return mixed[]
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return match ($filter->command) {
            $this->translator->trans('mautic.project.searchcommand.name'),
            $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US') => $this->handleProjectFilter(
                $this->_em->getConnection()->createQueryBuilder(),
                'message_id',
                'message_projects_xref',
                $this->getTableAlias(),
                $filter->string,
                $filter->not
            ),
            default => $this->addStandardSearchCommandWhereClause($q, $filter),
        };
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge([
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.project.searchcommand.name',
        ], parent::getSearchCommands());
    }
}
