<?php

namespace Mautic\EmailBundle\Swiftmailer\Sparkpost;

/**
 * Interface SparkpostFactoryInterface.
 */
interface SparkpostFactoryInterface
{
    /**
     * @param      $host
     * @param      $apiKey
     * @param null $port
     *
     * @return mixed
     */
    public function create($host, $apiKey, $port = null);
}
