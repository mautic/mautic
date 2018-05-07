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
     * @param string $host
     * @param string $apiKey
     *
     * @return SparkPost
     */
    public function create($host, $apiKey)
    {
        if ((strpos($host, '://') === false && substr($host, 0, 1) != '/')) {
            $host = 'https://'.$host;
        }
        $hostInfo = parse_url($host);
        if ($hostInfo) {
            return new SparkPost($this->client, [
                'host'     => $hostInfo['host'].$hostInfo['path'],
                'protocol' => $hostInfo['scheme'],
                'port'     => $hostInfo['scheme'] === 'https' ? 443 : 80,
                'key'      => $apiKey,
            ]);
        } else {
            // problem :/
        }
    }
}
