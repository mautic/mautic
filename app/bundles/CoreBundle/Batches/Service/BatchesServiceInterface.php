<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Service;

use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Mautic\CoreBundle\Batches\Runner\BatchRunnerInterface;
use Symfony\Component\HttpFoundation\Request;

interface BatchesServiceInterface
{
    /**
     * Create batch action runner from group
     *
     * @param Request               $request
     * @param BatchGroupInterface   $batchGroup
     * @param string                $actionName
     *
     * @throws BatchActionFailException
     *
     * @return BatchRunnerInterface
     */
    public function createRunnerFromGroup(Request $request, BatchGroupInterface $batchGroup, $actionName);
}