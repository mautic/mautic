<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Allows templates access values from CoreParametersHelper.
 *
 * Example usage:
 *
 * <?php echo $view['config']->get('default_timezone', 'UTC'); ?>
 */
final class ConfigHelper
{
    private \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->coreParametersHelper->get($name, $default);
    }

    public function getName(): string
    {
        return 'config';
    }
}
