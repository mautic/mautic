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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class CoreParametersHelper.
 */
class CoreParametersHelper
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * CoreParametersHelper constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->parameterBag = $kernel->getContainer()->getParameterBag();
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if ($name === 'db_table_prefix' && defined('MAUTIC_TABLE_PREFIX')) {
            //use the constant in case in the installer
            return MAUTIC_TABLE_PREFIX;
        }

        if ($this->parameterBag->has('mautic.'.$name)) {
            // Decode %%
            $value = str_replace('%%', '%', $this->parameterBag->get('mautic.'.$name));

            return $value;
        }

        // Last ditch effort in case we're getting non-mautic params
        if ($this->parameterBag->has($name)) {
            return $this->parameterBag->get($name);
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParameter($name)
    {
        return $this->parameterBag->has('mautic.'.$name);
    }

    /**
     * @return int
     */
    public function getMaxUploadSize()
    {
        return $this->getParameter('max_size');
    }

    /**
     * @return array
     */
    public function getAllowedExtensionsForUpload()
    {
        return $this->getParameter('allowed_extensions');
    }
}
