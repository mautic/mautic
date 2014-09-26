<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ApiBundle\Entity\oAuth2\Client;

/**
 * Class ClientEvent
 *
 * @package Mautic\RoleBundle\Event
 */
class ClientEvent extends CommonEvent
{

    /**
     * @param Client $client
     * @param bool $isNew
     */
    public function __construct(Client $client, $isNew = false)
    {
        $this->entity = $client;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Client entity
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->entity;
    }
}
