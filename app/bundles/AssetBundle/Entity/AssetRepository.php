<?php

namespace Mautic\AssetBundle\Entity;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Asset>
 */
class AssetRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    /**
     * Get a list of entities.
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
                ->setParameter('search', "%{$search}%");
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

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'a.title',
            'a.alias',
        ]);
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

        $command         = $field         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; // returning a parameter that is not used will lead to a Doctrine error
        $mediaCommandMap = [
            'mautic.asset.asset.searchcommand.isimage' => [
                ['image/%'],
                ['application/photoshop', 'application/x-photoshop'],
                ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tif', 'tiff', 'psd', 'ico'],
            ],
            'mautic.asset.asset.searchcommand.isvideo' => [
                ['video/%'],
                [],
                ['mp4', 'webm', 'ogv', 'mov', 'wmv', 'avi', 'mkv', 'mpeg'],
            ],
            'mautic.asset.asset.searchcommand.isaudio' => [
                ['audio/%'],
                [],
                ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'],
            ],
        ];
        switch ($command) {
            case $this->translator->trans('mautic.asset.asset.searchcommand.isexpired'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isexpired', [], null, 'en_US'):
                $expr = sprintf(
                    "(a.isPublished = :%1\$s AND a.publishDown IS NOT NULL AND a.publishDown <> '' AND a.publishDown < CURRENT_TIMESTAMP())",
                    $unique
                );
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.asset.asset.searchcommand.ispending'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.ispending', [], null, 'en_US'):
                $expr = sprintf(
                    "(a.isPublished = :%1\$s AND a.publishUp IS NOT NULL AND a.publishUp <> '' AND a.publishUp > CURRENT_TIMESTAMP())",
                    $unique
                );
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.asset.asset.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr            = '('.$q->expr()->eq('a.language', ":{$unique}").' OR '.$q->expr()->like('a.language', ":{$langUnique}").')';
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.asset.asset.searchcommand.isimage'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isimage', [], null, 'en_US'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isvideo'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isvideo', [], null, 'en_US'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isaudio'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isaudio', [], null, 'en_US'):
                foreach ($mediaCommandMap as $key => [$patterns, $exactMimes, $extensions]) {
                    if (in_array($command, [
                        $this->translator->trans($key),
                        $this->translator->trans($key, [], null, 'en_US'),
                    ], true)) {
                        [$expr, $forceParameters] = $this->buildMediaExpression($patterns, $exactMimes, $extensions);
                        break;
                    }
                }
                break;
            case $this->translator->trans('mautic.asset.asset.searchcommand.isdocument'):
            case $this->translator->trans('mautic.asset.asset.searchcommand.isdocument', [], null, 'en_US'):
                [$expr, $forceParameters] = $this->buildMediaExpression(
                    [],
                    [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-word.document.macroEnabled.12',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel.sheet.macroEnabled.12',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
                        'application/vnd.oasis.opendocument.text',
                        'application/vnd.oasis.opendocument.spreadsheet',
                        'application/vnd.oasis.opendocument.presentation',
                        'application/rtf',
                        'text/plain',
                        'text/csv',
                    ],
                    ['pdf', 'doc', 'docx', 'docm', 'xls', 'xlsx', 'xlsm', 'ppt', 'pptx', 'pptm', 'odt', 'ods', 'odp', 'rtf', 'txt', 'csv']
                );
                break;
            case $this->translator->trans('mautic.project.searchcommand.name'):
            case $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US'):
                return $this->handleProjectFilter(
                    $this->_em->getConnection()->createQueryBuilder(),
                    'asset_id',
                    'asset_projects_xref',
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
        } elseif (!$returnParameter) {
            $parameters = [];
        } else {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["{$unique}" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @param string[] $patterns
     * @param string[] $exactMimes
     * @param string[] $extensions
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildMediaExpression(array $patterns, array $exactMimes, array $extensions): array
    {
        $parameters  = [];
        $comparisons = [];

        foreach ($patterns as $pattern) {
            $name          = $this->generateRandomParameterName();
            $parameters[$name] = $pattern;
            $comparisons[] = "(a.mime IS NOT NULL AND a.mime LIKE :{$name})";
        }

        foreach ($exactMimes as $mime) {
            $exactName         = $this->generateRandomParameterName();
            $prefixName        = $this->generateRandomParameterName();
            $parameters[$exactName]  = $mime;
            $parameters[$prefixName] = $mime.';%';
            $comparisons[]     = "(a.mime IS NOT NULL AND (a.mime = :{$exactName} OR a.mime LIKE :{$prefixName}))";
        }

        $extensionComparisons = [];
        foreach ($extensions as $extension) {
            $name                   = $this->generateRandomParameterName();
            $parameters[$name]      = $extension;
            $extensionComparisons[] = "a.extension = :{$name}";
        }

        if ($extensionComparisons) {
            $comparisons[] = "((a.mime IS NULL OR a.mime = '' OR a.mime = 'application/octet-stream') AND a.extension IS NOT NULL AND (".implode(' OR ', $extensionComparisons).'))';
        }

        return ['('.implode(' OR ', $comparisons).')', $parameters];
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
            'mautic.asset.asset.searchcommand.isexpired',
            'mautic.asset.asset.searchcommand.ispending',
            'mautic.core.searchcommand.category',
            'mautic.asset.asset.searchcommand.lang',
            'mautic.asset.asset.searchcommand.isimage',
            'mautic.asset.asset.searchcommand.isvideo',
            'mautic.asset.asset.searchcommand.isdocument',
            'mautic.asset.asset.searchcommand.isaudio',
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
            ['a.title', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'a';
    }

    /**
     * Gets the sum size of assets.
     */
    public function getAssetSize(array $assets): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('sum(a.size) as total_size')
            ->from(MAUTIC_TABLE_PREFIX.'assets', 'a')
            ->where('a.id IN (:assetIds)')
            ->setParameter('assetIds', $assets, ArrayParameterType::INTEGER);

        $result = $q->executeQuery()->fetchAllAssociative();

        return (int) $result[0]['total_size'];
    }

    /**
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upDownloadCount($id, $increaseBy = 1, $unique = false): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'assets')
            ->set('download_count', 'download_count + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        if ($unique) {
            $q->set('unique_download_count', 'unique_download_count + '.(int) $increaseBy);
        }

        $q->executeStatement();
    }

    /**
     * @param int $categoryId
     *
     * @return Asset
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getLatestAssetForCategory($categoryId)
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($this->getTableAlias().'.category = :categoryId');
        $q->andWhere($this->getTableAlias().'.isPublished = TRUE');
        $q->setParameter('categoryId', $categoryId);
        $q->orderBy($this->getTableAlias().'.dateAdded', Order::Descending->value);
        $q->setMaxResults(1);

        return $q->getQuery()->getSingleResult();
    }

    /**
     * Finds a single Asset entity by its canonical UUID slug.
     *
     * @throws NonUniqueResultException
     * @throws EntityNotFoundException
     */
    public function findOneByUuid(string $slug): Asset
    {
        $asset = $this->createQueryBuilder('a')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$asset) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Asset::class, ['uuid' => $slug]);
        }

        return $asset;
    }
}
