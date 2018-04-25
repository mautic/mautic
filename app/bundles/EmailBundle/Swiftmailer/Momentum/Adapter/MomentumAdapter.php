<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use Mautic\EmailBundle\Swiftmailer\Sparkpost\SparkpostFactoryInterface;
use SparkPost\SparkPost;
use SparkPost\SparkPostPromise;

/**
 * Class MomentumAdapter.
 */
final class MomentumAdapter implements MomentumAdapterInterface
{
    /**
     * @var SparkPost
     */
    private $sparkpost;

    /**
     * MomentumAdapter constructor.
     *
     * @param string                    $apiKey
     * @param SparkpostFactoryInterface $sparkpostFactory
     */
    public function __construct($apiKey, SparkpostFactoryInterface $sparkpostFactory)
    {
        $this->sparkpost = $sparkpostFactory->create($apiKey);
    }

    /**
     * @param array $message
     *
     * @return SparkPostPromise
     */
    public function send(array $message = [])
    {
        return $this->sparkpost->transmissions->post($message);
    }
}
