<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;

interface SyncJudgeInterface
{
    /**
     * Winner is selected based on the field was updated after the loser.
     */
    public const string HARD_EVIDENCE_MODE = 'hard';

    /**
     * Winner is selected based on hard evidence if available, otherwise if the object of the winner was updated after the object of the loser.
     */
    public const string BEST_EVIDENCE_MODE = 'best';

    /**
     * Winner is selected based on the probability that it was updated after the loser.
     */
    public const string FUZZY_EVIDENCE_MODE = 'fuzzy';

    public const string LEFT_WINNER  = 'left';

    public const string RIGHT_WINNER = 'right';

    public const string NO_WINNER    = 'no';

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
        InformationChangeRequestDAO $rightChangeRequest,
    );
}
