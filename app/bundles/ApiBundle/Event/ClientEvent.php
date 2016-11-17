<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Event;

use Mautic\ApiBundle\Entity\oAuth1\Consumer;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ClientEvent.
 */
class ClientEvent extends CommonEvent
{
    /**
     * @var string
     */
    private $apiMode;

    /**
     * @param Client|Consumer $client
     * @param bool            $isNew
     */
    public function __construct($client, $isNew = false)
    {
        if (!$client instanceof Client && !$client instanceof Consumer) {
            throw new MethodNotAllowedHttpException(['Client', 'Consumer']);
        }

        $this->apiMode = ($client instanceof Client) ? 'oauth2' : 'oauth1';

        $this->entity = $client;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Client entity.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->entity;
    }

    /**
     * Returns the api mode.
     *
     * @return string
     */
    public function getApiMode()
    {
        return $this->apiMode;
    }
}
