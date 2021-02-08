<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
