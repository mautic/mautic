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
     * @param      $host
     * @param      $apiKey
     * @param null $port
     *
     * @return mixed|SparkPost
     */
    public function create($host, $apiKey, $port = null)
    {
        if ((strpos($host, '://') === false && substr($host, 0, 1) != '/')) {
            $host = 'https://'.$host;
        }

        $hostInfo = parse_url($host);

        if ($hostInfo) {
            if (empty($port)) {
                $port = $hostInfo['scheme'] === 'https' ? 443 : 80;
            }

            $options = [
                'protocol' => $hostInfo['scheme'],
                'port'     => $port,
                'key'      => $apiKey,
            ];

            $host = $hostInfo['host'];
            if (isset($hostInfo['path'])) {
                $path = $hostInfo['path'];
                if (preg_match('~/api/(v\d+)$~i', $path, $matches)) {
                    // Remove /api from the path and extract the version in case differnt than the Sparkpost SDK default
                    $path               = str_replace($matches[0], '', $path);
                    $options['version'] = $matches[1];
                }

                // Append whatever is left over to the host (assuming Momentum can be in a subfolder?)
                if ('/' !== $path) {
                    $host .= $path;
                }
            }

            $options['host'] = $host;

            return new SparkPost($this->client, $options);
        } else {
            // problem :/
        }
    }
}
