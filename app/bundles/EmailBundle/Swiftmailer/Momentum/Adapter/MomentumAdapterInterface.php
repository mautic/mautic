<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use SparkPost\SparkPostPromise;

/**
 * Interface MomentumAdapterInterface.
 */
interface MomentumAdapterInterface
{
    /**
     * @param array $message
     *
     * @return SparkPostPromise
     */
    public function send(array $message = []);
}
