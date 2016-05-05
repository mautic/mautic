<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class CoreParametersHelper
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
     * 
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->parameterBag->get($name);
    }

    /**
     * @param string $name
     * 
     * @return bool
     */
    public function hasParameter($name)
    {
        return $this->parameterBag->has($name);
    }
}
