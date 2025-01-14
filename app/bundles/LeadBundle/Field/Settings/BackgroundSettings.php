<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Settings;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class BackgroundSettings
{
    public const CREATE_CUSTOM_FIELD_IN_BACKGROUND = 'create_custom_field_in_background';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function shouldProcessColumnChangeInBackground(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::CREATE_CUSTOM_FIELD_IN_BACKGROUND, false);
    }
}
