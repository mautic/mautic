<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * @extends CommonRepository<User>
 */
class UserRepository extends CommonRepository
{
    /**
     * Find user by username or email.
     */
    public function findByIdentifier(string $identifier): ?User
    {
        $q = $this->createQueryBuilder('u')
            ->where('u.username = :identifier OR u.email = :identifier')
            ->setParameter('identifier', $identifier);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? $result[0] : null;
    }

    public function setLastLogin($user): void
    {
        $now      = new DateTimeHelper();
        $datetime = $now->toUtcString();
        $conn     = $this->_em->getConnection();
        $conn->update(MAUTIC_TABLE_PREFIX.'users', [
            'last_login'  => $datetime,
            'last_active' => $datetime,
        ], ['id' => (int) $user->getId()]);
    }

    public function setLastActive($user): void
    {
        $now  = new DateTimeHelper();
        $conn = $this->_em->getConnection();
        $conn->update(MAUTIC_TABLE_PREFIX.'users', ['last_active' => $now->toUtcString()], ['id' => (int) $user->getId()]);
    }

    /**
     * Checks to ensure that a username and/or email is unique.
     *
     * @return array
     */
    public function checkUniqueUsernameEmail($params)
    {
        $q = $this->createQueryBuilder('u');

        if (isset($params['email'])) {
            $q->where('u.username = :email OR u.email = :email')
                ->setParameter('email', $params['email']);
        }

        if (isset($params['username'])) {
            $q->orWhere('u.username = :username OR u.email = :username')
                ->setParameter('username', $params['username']);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Get a list of users.
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select('u, r')
            ->leftJoin('u.role', 'r');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get a list of users for an autocomplete input.
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     * @param array  $permissionLimiter
     *
     * @return array
     */
    public function getUserList($search = '', $limit = 10, $start = 0, $permissionLimiter = [])
    {
        $q = $this->_em->createQueryBuilder();

        $q->select('partial u.{id, firstName, lastName}')
            ->from(\Mautic\UserBundle\Entity\User::class, 'u')
            ->leftJoin('u.role', 'r')
            ->leftJoin('r.permissions', 'p');

        if (!empty($search)) {
            $q->where(
                $q->expr()->orX(
                    $q->expr()->like('u.firstName', ':search'),
                    $q->expr()->like('u.lastName', ':search'),
                    $q->expr()->like(
                        $q->expr()->concat('u.firstName',
                            $q->expr()->concat(
                                $q->expr()->literal(' '),
                                'u.lastName'
                            )
                        ),
                        ':search'
                    )
                )
            )
            ->setParameter('search', "{$search}%");
        }

        if (!empty($permissionLimiter)) {
            // only get users with a role that has some sort of access to set permissions
            $expr = $q->expr()->andX();
            foreach ($permissionLimiter as $bundle => $level) {
                $expr->add(
                    $q->expr()->andX(
                        $q->expr()->eq('p.bundle', $q->expr()->literal($bundle)),
                        $q->expr()->eq('p.name', $q->expr()->literal($level))
                    )
                );
            }
            $expr = $q->expr()->orX(
                $q->expr()->eq('r.isAdmin', ':true'),
                $expr
            );
            $q->andWhere($expr);
        }

        $q->andWhere('u.isPublished = :true')
            ->setParameter('true', true, 'boolean')
            ->orderBy('u.firstName, u.lastName');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Return list of Users for formType Choice.
     */
    public function getOwnerListChoices(): array
    {
        $q = $this->createQueryBuilder('u');

        $q->select('partial u.{id, firstName, lastName}');

        $q->andWhere('u.isPublished = true')
            ->orderBy('u.firstName, u.lastName');

        $users = $q->getQuery()->getResult();

        $result = [];
        /** @var User $user */
        foreach ($users as $user) {
            $result[$user->getName(true)] = $user->getId();
        }

        return $result;
    }

    /**
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getPositionList($search = '', $limit = 10, $start = 0)
    {
        $q = $this->_em->createQueryBuilder()
            ->select('u.position')
            ->distinct()
            ->from(\Mautic\UserBundle\Entity\User::class, 'u')
            ->where("u.position != ''")
            ->andWhere('u.position IS NOT NULL');
        if (!empty($search)) {
            $q->andWhere('u.position LIKE :search')
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy('u.position');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                'u.username',
                'u.email',
                'u.firstName',
                'u.lastName',
                'u.position',
                'r.name',
            ]
        );
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        $command                 = $filter->command;
        $unique                  = $this->generateRandomParameterName();
        $returnParameter         = false; // returning a parameter that is not used will lead to a Doctrine error
        [$expr, $parameters]     = parent::addSearchCommandWhereClause($q, $filter);

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('u.isPublished', ":$unique");
                $forceParameters = [$unique => true];

                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('u.isPublished', ":$unique");
                $forceParameters = [$unique => false];

                break;
            case $this->translator->trans('mautic.user.user.searchcommand.isadmin'):
            case $this->translator->trans('mautic.user.user.searchcommand.isadmin', [], null, 'en_US'):
                $expr            = $q->expr()->eq('r.isAdmin', ":$unique");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.email'):
            case $this->translator->trans('mautic.core.searchcommand.email', [], null, 'en_US'):
                $expr            = $q->expr()->like('u.email', ':'.$unique);
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.user.user.searchcommand.position'):
            case $this->translator->trans('mautic.user.user.searchcommand.position', [], null, 'en_US'):
                $expr            = $q->expr()->like('u.position', ':'.$unique);
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.user.user.searchcommand.username'):
            case $this->translator->trans('mautic.user.user.searchcommand.username', [], null, 'en_US'):
                $expr            = $q->expr()->like('u.username', ':'.$unique);
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.user.user.searchcommand.role'):
            case $this->translator->trans('mautic.user.user.searchcommand.role', [], null, 'en_US'):
                $expr            = $q->expr()->like('r.name', ':'.$unique);
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                // This if/else can be removed once we upgrade to Dotrine 2.11 as both builders have the or() method there.
                if ($q instanceof QueryBuilder) {
                    $expr = $q->expr()->or(
                        $q->expr()->like('u.firstName', ':'.$unique),
                        $q->expr()->like('u.lastName', ':'.$unique)
                    );
                } else {
                    $expr = $q->expr()->orX(
                        $q->expr()->like('u.firstName', ':'.$unique),
                        $q->expr()->like('u.lastName', ':'.$unique)
                    );
                }
                $returnParameter = true;
                break;
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.core.searchcommand.email',
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.user.user.searchcommand.isadmin',
            'mautic.core.searchcommand.name',
            'mautic.user.user.searchcommand.position',
            'mautic.user.user.searchcommand.role',
            'mautic.user.user.searchcommand.username',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    protected function getDefaultOrder(): array
    {
        return [
            ['u.lastName', 'ASC'],
            ['u.firstName', 'ASC'],
            ['u.username', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'u';
    }
}
