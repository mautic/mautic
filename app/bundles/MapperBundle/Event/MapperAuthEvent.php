<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MapperAuthEvent
 *
 * @package Mautic\MapperBundle\Event
 */
class MapperAuthEvent extends Event
{
    /**
     * @var string
     */
    protected $application;

    /**
     * @var string
     */
    protected $client;

    /**
     * @var array
     */
    protected $postActionRedirect = array();

    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    protected $security;

    /**
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @param $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * @return string
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return mixed
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @return array
     */
    public function getPostActionRedirect()
    {
        return $this->postActionRedirect;
    }

    /**
     * @param array $postActionRedirect
     */
    public function setPostActionRedirect(array $postActionRedirect)
    {
        $this->postActionRedirect = $postActionRedirect;
    }
}