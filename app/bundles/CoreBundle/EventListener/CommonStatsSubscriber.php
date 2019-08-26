<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Event\StatsEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class CommonStatsSubscriber.
 */
abstract class CommonStatsSubscriber extends CommonSubscriber
{
    /**
     * @var array of CommonRepository
     */
    protected $repositories = [];

    /**
     * @var null
     */
    protected $selects = null;

    /**
     * @var array
     */
    protected $permissions = [];

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::LIST_STATS => ['onStatsFetch', 0],
        ];
    }

    /**
     * @param StatsEvent $event
     */
    public function onStatsFetch(StatsEvent $event)
    {
        /** @var CommonRepository $repository */
        foreach ($this->repositories as $repoName => $repository) {
            $table = $repository->getTableName();

            if (!$event->isLookingForTable($table, $repository)) {
                continue;
            }

            $permissions  = (isset($this->permissions[$table])) ? $this->permissions[$table] : [];
            $allowedJoins = [];
            $canLoad      = false;

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

            $select = (isset($this->selects[$table])) ? $this->selects[$table] : null;
            $event->setSelect($select)->setRepository($repository, $allowedJoins);
        }
    }

    /**
     * Restrict stats based on contact permissions.
     *
     * @param EntityManager $em
     * @param               $repoNames
     *
     * @return $this
     */
    protected function addContactRestrictedRepositories(EntityManager $em, $repoNames)
    {
        return $this->addRestrictedRepostories($em, $repoNames, ['lead' => 'lead:leads']);
    }

    /**
     * @param EntityManager $em
     * @param               $repoNames
     * @param array         $permissions
     */
    protected function addRestrictedRepostories(EntityManager $em, $repoNames, array $permissions)
    {
        if (!is_array($repoNames)) {
            $repoNames = [$repoNames];
        }

        foreach ($repoNames as $repoName) {
            $this->repositories[]      = $repo      = $em->getRepository($repoName);
            $table                     = $repo->getTableName();
            $this->permissions[$table] = $permissions;
        }

        return $this;
    }
}
