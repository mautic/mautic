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
    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
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
