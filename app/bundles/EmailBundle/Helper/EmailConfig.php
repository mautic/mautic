<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class EmailConfig implements EmailConfigInterface
{
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    public function isDraftEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('email_draft_enabled', false);
    }
}
