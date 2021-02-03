<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Stat;
use Symfony\Component\EventDispatcher\Event;

final class EmailStatEvent extends Event
{
    /**
     * @var Stat[]
     */
    private $stats;

    /**
     * @var Stat[]
     */
    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    /**
     * @return Stat[]
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
