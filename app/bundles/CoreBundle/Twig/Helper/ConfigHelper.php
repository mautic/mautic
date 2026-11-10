<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Allows templates access values from CoreParametersHelper.
 *
 * Example usage:
 *
 * <?php echo $view['config']->get('default_timezone', 'UTC'); ?>
 */
final readonly class ConfigHelper
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * @return mixed
     */
    public function get(string $name, mixed $default = null)
    {
        return $this->coreParametersHelper->get($name, $default);
    }

    public function getName(): string
    {
        return 'config';
    }
}
