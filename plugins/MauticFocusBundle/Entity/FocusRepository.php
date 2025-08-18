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

        $styleMapping = [
            'mautic.focus.focus.searchcommand.stylebar'          => 'bar',
            'mautic.focus.focus.searchcommand.stylemodal'        => 'modal',
            'mautic.focus.focus.searchcommand.stylenotification' => 'notification',
            'mautic.focus.focus.searchcommand.stylefullpage'     => 'page',
        ];

        if (isset($styleMapping[$command]) || $this->isTranslatedCommand($command, array_keys($styleMapping))) {
            $expr            = $q->expr()->eq('f.style', ":$unique");
            $forceParameters = [$unique => $styleMapping[$command] ?? $this->getStyleValueFromCommand($command, $styleMapping)];
        } elseif ($this->isTranslatedCommand($command, ['mautic.project.searchcommand.name'])) {
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
     * Check if a command matches any of the given translation keys (including fallback to en_US)
     *
     * @param array<string> $translationKeys
     */
    private function isTranslatedCommand(string $command, array $translationKeys): bool
    {
        foreach ($translationKeys as $key) {
            if ($command === $this->translator->trans($key) ||
                $command === $this->translator->trans($key, [], null, 'en_US')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the style value from a translated command
     *
     * @param array<string, string> $styleMapping
     */
    private function getStyleValueFromCommand(string $command, array $styleMapping): ?string
    {
        foreach ($styleMapping as $key => $value) {
            if ($command === $this->translator->trans($key) ||
                $command === $this->translator->trans($key, [], null, 'en_US')) {
                return $value;
            }
        }
        return null;
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
