<?php

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Event\StatsEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class CommonStatsSubscriber implements EventSubscriberInterface
{
    /**
     * @var mixed[]
     */
    protected $repositories = [];

    /**
     * @var array<string, mixed>
     */
    protected array $selects = [];

    /**
     * @var array
     */
    protected $permissions = [];

    public function __construct(
        protected CorePermissions $security,
        protected EntityManager $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::LIST_STATS => ['onStatsFetch', 0],
        ];
    }

    public function onStatsFetch(StatsEvent $event): void
    {
        /** @var CommonRepository<object> $repository */
        foreach ($this->repositories as $repository) {
            $table = $repository->getTableName();

            if (!$event->isLookingForTable($table, $repository)) {
                continue;
            }

            $permissions  = $this->permissions[$table] ?? [];
            $allowedJoins = [];

            foreach ($permissions as $tableAlias => $permBase) {
                // It's an admin, don't check any further
                if ('admin' === $permBase && $this->security->isAdmin()) {
                    continue;
                }

                // This user can view everything from this entity, don't check any furher
                if ($this->security->checkPermissionExists($permBase.':view') && $this->security->isGranted($permBase.':view')) {
                    continue;
                }

                // This user can view own entities, limit the search
                if ($this->security->checkPermissionExists($permBase.':viewother') && $this->security->isGranted($permBase.':viewother')
                ) {
                    $userId = $event->getUser()->getId();
                    $where  = [
                        'internal' => true,
                        'expr'     => 'formula',
                    ];

                    // In case the table alias is defined as an association such as stat.email
                    $aliasParts = explode('.', $tableAlias);
                    $tableAlias = array_pop($aliasParts);

                    if ('lead:leads' === $permBase) {
                        // Acknowledge owner then created_by
                        $where['value'] = "IF ($tableAlias.owner_id IS NOT NULL, $tableAlias.owner_id, $tableAlias.created_by) = $userId";
                    } else {
                        $where['value'] = "$tableAlias.created_by = $userId";
                    }
                    $event->addWhere($where);

                    $allowedJoins[] = $tableAlias;
                    continue;
                }

                throw new AccessDeniedException(sprintf('You do not have the view permission to load data from the %s table', $tableAlias));
            }

            $select = $this->selects[$table] ?? null;
            $event->setSelect($select)->setRepository($repository, $allowedJoins);
        }
    }

    /**
     * Restrict stats based on contact permissions.
     *
     * @return $this
     */
    protected function addContactRestrictedRepositories(array $repoNames)
    {
        return $this->addRestrictedRepostories($repoNames, ['lead' => 'lead:leads']);
    }

    protected function addRestrictedRepostories(array $repoNames, array $permissions)
    {
        foreach ($repoNames as $repoName) {
            $repo                      = $this->entityManager->getRepository($repoName);
            $this->repositories[]      = $repo;
            $table                     = $repo->getTableName();
            $this->permissions[$table] = $permissions;
        }

        return $this;
    }
}
