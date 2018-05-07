<?php

namespace Mautic\EmailBundle\Swiftmailer\Sparkpost;

use SparkPost\SparkPost;

/**
 * Interface SparkpostFactoryInterface.
 */
interface SparkpostFactoryInterface
{
    /**
     * @param string $host
     * @param string $apiKey
     *
     * @return SparkPost
     */
    public function create($host, $apiKey);
}
