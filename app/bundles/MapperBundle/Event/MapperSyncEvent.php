<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Event;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MapperAuthEvent
 *
 * @package Mautic\MapperBundle\Event
 */
class MapperSyncEvent extends Event
{
    /**
     * @var string
     */
    protected $mapper = '';
    protected $data;
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $mapper
     * @return $this
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @return string
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}