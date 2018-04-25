<?php

namespace Mautic\EmailBundle\Swiftmailer\Sparkpost;

use SparkPost\SparkPost;

/**
 * Interface SparkpostFactoryInterface.
 */
interface SparkpostFactoryInterface
{
    /**
     * @param string $apiKey
     *
     * @return SparkPost
     */
    public function create($apiKey);
}
