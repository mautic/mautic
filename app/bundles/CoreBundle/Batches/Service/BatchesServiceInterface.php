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

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Runner\BatchRunnerInterface;
use Symfony\Component\HttpFoundation\Request;

interface BatchesServiceInterface
{
    /**
     * Create runner of single action
     *
     * @param Request               $request
     * @param BatchActionInterface  $batchAction
     *
     * @throws BatchActionFailException
     *
     * @return BatchRunnerInterface
     */
    public function createRunner(Request $request, BatchActionInterface $batchAction);
}