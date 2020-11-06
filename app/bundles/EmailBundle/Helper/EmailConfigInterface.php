<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Entity\Email;

interface EmailConfigInterface
{
    public function isDraftEnabled(): bool;
}
