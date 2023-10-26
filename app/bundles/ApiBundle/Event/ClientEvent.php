<?php

namespace Mautic\ApiBundle\Event;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class ClientEvent.
 */
class ClientEvent extends CommonEvent
{
    private string $apiMode;

    public function __construct(Client $client, $isNew = false)
    {
        $this->apiMode = 'oauth2';
        $this->entity  = $client;
        $this->isNew   = $isNew;
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
