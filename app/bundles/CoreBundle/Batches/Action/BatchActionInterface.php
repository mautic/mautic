<?php

namespace Mautic\CoreBundle\Batches\Action;

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Batches\Request\BatchRequestInterface;

interface BatchActionInterface
{
    /**
     * Run a batch action. Result is count of processed objects.
     *
     * @param BatchRequestInterface $batchRequest
     *
     * @return int
     */
    public function run(BatchRequestInterface $batchRequest);
}
