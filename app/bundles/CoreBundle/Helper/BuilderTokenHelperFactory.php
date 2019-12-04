<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class BuilderTokenHelperFactory
{
    private $security;
    private $entityManager;
    private $connection;
    private $userHelper;

    /**
     * @param CorePermissions $security
     * @param EntityManager   $entityManager
     * @param Connection      $connection
     * @param UserHelper      $userHelper
     */
    public function __construct(
        CorePermissions $security,
        EntityManager $entityManager,
        Connection $connection,
        UserHelper $userHelper
    ) {
        $this->security      = $security;
        $this->entityManager = $entityManager;
        $this->connection    = $connection;
        $this->userHelper    = $userHelper;
    }

    /**
     * @param $modelName
     * @param null $viewPermissionBase
     * @param null $bundleName
     * @param null $langVar
     *
     * @return BuilderTokenHelper
     */
    public function getBuilderTokenHelper($modelName, $viewPermissionBase = null, $bundleName = null, $langVar = null)
    {
        $builderTokenHelper = new BuilderTokenHelper($this->security, $this->entityManager, $this->connection, $this->userHelper);
        $builderTokenHelper->configure($modelName, $viewPermissionBase, $bundleName, $langVar);

        return $builderTokenHelper;
    }
}
