<?php

/*
 * @copyright   2014 Mautic, Na. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class AssetRepository.
 */
class AssetRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->leftJoin('a.category', 'c');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param string     $search
     * @param int        $limit
     * @param int        $start
     * @param bool|false $viewOther
     *
     * @return array
     */
    public function getAssetList($search = '', $limit = 10, $start = 0, $viewOther = false)
    {
        $q = $this->createQueryBuilder('a');
        $q->select('partial a.{id, title, path, alias, language}');

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('a.title', ':search'))
                ->setParameter('search', "{$search}%");
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('a.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        $q->orderBy('a.title');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'a.title',
            'a.alias',
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        list($expr, $parameters) = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $field         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; //returning a parameter that is not used will lead to a Doctrine error
        switch ($command) {
            case $this->translator->trans('mautic.asset.asset.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr = $q->expr()->orX(
                    $q->expr()->eq('a.language', ":$unique"),
                    $q->expr()->like('a.language', ":$langUnique")
                );
                $returnParameter = true;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif (!$returnParameter) {
            $parameters = [];
        } else {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.asset.asset.searchcommand.lang',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            ['a.title', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'a';
    }

    /**
     * Gets the sum size of assets.
     *
     * @param array $assets
     *
     * @return int
     */
    public function getAssetSize(array $assets)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('sum(a.size) as total_size')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 'a')
            ->where('a.id IN (:assetIds)')
            ->setParameter('assetIds', $assets, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $result = $q->execute()->fetchAll();

        return (int) $result[0]['total_size'];
    }

    /**
     * @param            $id
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upDownloadCount($id, $increaseBy = 1, $unique = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'assets')
            ->set('download_count', 'download_count + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        if ($unique) {
            $q->set('unique_download_count', 'unique_download_count + '.(int) $increaseBy);
        }

        $q->execute();
    }

    /**
     * @param int $categoryId
     *
     * @return Asset
     *
     * @throws NoResultException
     */
    public function getLatestAssetForCategory($categoryId)
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($this->getTableAlias().'.category = :categoryId');
        $q->andWhere($this->getTableAlias().'.isPublished = TRUE');
        $q->setParameter('categoryId', $categoryId);
        $q->orderBy($this->getTableAlias().'.dateAdded', 'DESC');
        $q->setMaxResults(1);

        return $q->getQuery()->getSingleResult();
    }
}
