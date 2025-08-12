<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ProjectBundle\Entity\ProjectRepositoryTrait;

/**
 * @extends CommonRepository<Focus>
 */
class FocusRepository extends CommonRepository
{
    use ProjectRepositoryTrait;

    /**
     * @return array
     */
    public function findByForm($formId)
    {
        return $this->findBy(
            [
                'form' => (int) $formId,
            ]
        );
    }

    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from(Focus::class, $alias, $alias.'.id');

        if (empty($args['iterable_mode'])) {
            $q->leftJoin($alias.'.category', 'c');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['f.name', 'f.website']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $standardSearchParameters] = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $standardSearchParameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $parameters      = [];
        $forceParameters = [];

        switch ($command) {
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylebar'):
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylebar', [], null, 'en_US'):
                $expr            = $q->expr()->eq('f.style', ":$unique");
                $forceParameters = [$unique => 'bar'];
                break;
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylemodal'):
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylemodal', [], null, 'en_US'):
                $expr            = $q->expr()->eq('f.style', ":$unique");
                $forceParameters = [$unique => 'modal'];
                break;
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylenotification'):
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylenotification', [], null, 'en_US'):
                $expr            = $q->expr()->eq('f.style', ":$unique");
                $forceParameters = [$unique => 'notification'];
                break;
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylefullpage'):
            case $this->translator->trans('mautic.focus.focus.searchcommand.stylefullpage', [], null, 'en_US'):
                $expr            = $q->expr()->eq('f.style', ":$unique");
                $forceParameters = [$unique => 'page'];
                break;
            case $this->translator->trans('mautic.project.searchcommand.name'):
            case $this->translator->trans('mautic.project.searchcommand.name', [], null, 'en_US'):
                return $this->handleProjectFilter(
                    $this->_em->getConnection()->createQueryBuilder(),
                    'focus_id',
                    'focus_projects_xref',
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

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge([
            'mautic.project.searchcommand.name',
            'mautic.focus.focus.searchcommand.stylebar',
            'mautic.focus.focus.searchcommand.stylemodal',
            'mautic.focus.focus.searchcommand.stylenotification',
            'mautic.focus.focus.searchcommand.stylefullpage',
        ], $this->getStandardSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'f';
    }

    /**
     * @return array
     */
    public function getFocusList($currentId)
    {
        $q = $this->createQueryBuilder('f');
        $q->select('partial f.{id, name, description}')->orderBy('f.name');

        return $q->getQuery()->getArrayResult();
    }
}
