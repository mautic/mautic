<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Event;

use Mautic\ApiBundle\Entity\oAuth1\Consumer;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ClientEvent
 *
 * @package Mautic\RoleBundle\Event
 */
class ClientEvent extends CommonEvent
{
    private $apiMode;

    /**
     * @param Client|Consumer $client
     * @param bool $isNew
     */
    public function __construct($client, $isNew = false)
    {
        if (!$client instanceof Client && !$client instanceof Consumer) {
            throw new MethodNotAllowedHttpException(array('Client', 'Consumer'));
        }

        $this->apiMode = ($client instanceof Client) ? 'oauth2' : 'oauth1';

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

    /**
     * Returns the api mode
     *
     * @return string
     */
    public function getApiMode()
    {
        return $this->apiMode;
    }
}
