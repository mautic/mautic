<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<EmailForward>
 */
final class EmailForwardRepository extends CommonRepository
{
    public function countByStat(Stat $stat): int
    {
        return $this->count(['stat' => $stat]);
    }
}
