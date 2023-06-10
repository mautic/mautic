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
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    /**
     * @return mixed
     */
    public function get(string $name, mixed $default = null)
    {
        return $this->coreParametersHelper->get($name, $default);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'config';
    }
}
