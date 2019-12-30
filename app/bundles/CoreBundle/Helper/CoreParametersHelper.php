<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Loader\ParameterLoader;

/**
 * Class CoreParametersHelper.
 */
class CoreParametersHelper
{
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    private $parameters;

    public function __construct()
    {
        $loader = new ParameterLoader();

        $this->parameters = $loader->getParameterBag();
    }

    public function getParameter($name, $default = null)
    {
        $name = $this->stripMauticPrefix($name);

        if ('db_table_prefix' === $name && defined('MAUTIC_TABLE_PREFIX')) {
            //use the constant in case in the installer
            return MAUTIC_TABLE_PREFIX;
        }

        return $this->parameters->get($name, $default);
    }

    public function hasParameter($name): bool
    {
        return $this->parameters->has($this->stripMauticPrefix($name));
    }

    public function allParameters(): array
    {
        return $this->parameters->all();
    }

    private function stripMauticPrefix(string $name): string
    {
        return str_replace('mautic.', '', $name);
    }
}
