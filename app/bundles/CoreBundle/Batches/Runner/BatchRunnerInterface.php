<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Runner;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Runner that run a batch action according to request
 */
interface BatchRunnerInterface
{
    /**
     * BatchRunnerInterface constructor.
     *
     * @param BatchActionInterface $batchAction
     * @param Request $request
     */
    public function __construct(BatchActionInterface $batchAction, Request $request);

    /**
     * Run a batch action. Result is count of processed objects.
     *
     * @throws BatchActionFailException
     *
     * @return int
     */
    public function run();
}