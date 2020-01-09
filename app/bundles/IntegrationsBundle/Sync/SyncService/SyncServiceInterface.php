<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\SyncService;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

interface SyncServiceInterface
{
    public function processIntegrationSync(InputOptionsDAO $inputOptionsDAO);
}
