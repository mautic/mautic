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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailConfigInterface;

final class EmailConfig implements EmailConfigInterface
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function isDraftEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('email_draft_enabled', false);
    }
}
