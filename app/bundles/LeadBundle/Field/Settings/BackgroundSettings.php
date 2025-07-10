<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Settings;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class BackgroundSettings
{
    public const CREATE_CUSTOM_FIELD_IN_BACKGROUND = 'create_custom_field_in_background';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function shouldProcessColumnChangeInBackground(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::CREATE_CUSTOM_FIELD_IN_BACKGROUND, false);
    }
}
