<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class ConfigEvent
 */
class ConfigEvent extends CommonEvent
{
    /**
     * @param array $config
     */
    private $config;

    /**
     * @param Symfony\Component\HttpFoundation\ParameterBag $post
     */
    private $post;

    /**
     * @param array $config
     */
    public function __construct(array $config, \Symfony\Component\HttpFoundation\ParameterBag $post)
    {
        $this->config = $config;
        $this->post   = $post;
    }

    /**
     * Returns the config array
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Sets the config array
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the POST
     *
     * @return Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getPost()
    {
        return $this->post;
    }
}
