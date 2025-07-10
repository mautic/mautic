<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncService;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

interface SyncServiceInterface
{
    public function processIntegrationSync(InputOptionsDAO $inputOptionsDAO);
}
