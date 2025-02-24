<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

class BuilderTokenHelperFactory
{
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        private CorePermissions $security,
        private ModelFactory $modelFactory,
        private Connection $connection,
        private UserHelper $userHelper
    ) {
    }

    public function getBuilderTokenHelper(
        string $modelName,
        ?string $viewPermissionBase = null,
        ?string $bundleName = null,
        ?string $langVar = null
    ): BuilderTokenHelper {
        $builderTokenHelper = new BuilderTokenHelper($this->security, $this->modelFactory, $this->connection, $this->userHelper);
        $builderTokenHelper->configure($modelName, $viewPermissionBase, $bundleName, $langVar);

        return $builderTokenHelper;
    }
}
