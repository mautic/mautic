<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Integration;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\MapperBundle\Entity\Application;

abstract class AbstractIntegration
{
    protected $factory;
    protected $entity;
    protected $settings;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Determines what priority the network should have against the other networks
     *
     * @return mixed
     */
    public function getPriority()
    {
        return 9999;
    }

    /**
     * Returns the alias for the application
     *
     * @return string
     */
    abstract public function getAppAlias();

    /**
     * Returns the name of the application
     *
     * @return string
     */
    abstract public function getAppName();

    /**
     * Returns the image source from application
     *
     * @return string
     */
    abstract public function getImage();
}