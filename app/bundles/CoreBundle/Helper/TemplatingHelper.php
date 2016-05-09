<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TemplatingHelper
 */
class TemplatingHelper
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * TemplatingHelper constructor.
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieve the templating service
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    public function getTemplating()
    {
        if (defined('IN_MAUTIC_CONSOLE')) {
            //enter the request scope in order to be use the templating.helper.assets service
            $this->container->enterScope('request');
            $this->container->set('request', new Request(), 'request');
        }

        return $this->container->get('templating');
    }
}
