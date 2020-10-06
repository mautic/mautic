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

namespace Mautic\IntegrationsBundle\Sync\SyncJudge;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;

interface SyncJudgeInterface
{
    /**
     * Winner is selected based on the field was updated after the loser.
     */
    public const HARD_EVIDENCE_MODE = 'hard';

    /**
     * Winner is selected based on hard evidence if available, otherwise if the object of the winner was updated after the object of the loser.
     */
    public const BEST_EVIDENCE_MODE = 'best';

    /**
     * Winner is selected based on the probability that it was updated after the loser.
     */
    public const FUZZY_EVIDENCE_MODE = 'fuzzy';

    public const LEFT_WINNER  = 'left';
    public const RIGHT_WINNER = 'right';
    public const NO_WINNER    = 'no';

    /**
     * @param string $mode
     *
     * @return InformationChangeRequestDAO
     *
     * @throws ConflictUnresolvedException
     */
    public function adjudicate(
        $mode,
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest
    );
}
