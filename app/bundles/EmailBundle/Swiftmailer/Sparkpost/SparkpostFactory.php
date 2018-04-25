<?php

namespace Mautic\EmailBundle\Swiftmailer\Sparkpost;

use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use SparkPost\SparkPost;

/**
 * Class SparkpostFactory.
 */
final class SparkpostFactory implements SparkpostFactoryInterface
{
    /**
     * @var GuzzleAdapter
     */
    private $client;

    /**
     * SparkpostFactory constructor.
     *
     * @param GuzzleAdapter $client
     */
    public function __construct(GuzzleAdapter $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $apiKey
     *
     * @return SparkPost
     */
    public function create($apiKey)
    {
        return new SparkPost($this->client, ['key' => $apiKey]);
    }
}
