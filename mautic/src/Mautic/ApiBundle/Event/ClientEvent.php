<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Mautic\ApiBundle\Entity\Client;

/**
 * Class ClientEvent
 *
 * @package Mautic\RoleBundle\Event
 */
class ClientEvent extends Event
{
    /**
     * @var \Mautic\ApiBundle\Entity\Client
     */
    protected $client;

    /**
     * @var
     */
    protected $isNew;

    /**
     * @param Client $client
     * @param bool $isNew
     */
    public function __construct(Client $client, $isNew = false)
    {
        $this->client = $client;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Client entity
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns if a saved client is new or not
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }
}
