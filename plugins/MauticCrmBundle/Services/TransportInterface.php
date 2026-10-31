<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Services;

interface TransportInterface
{
    public function post($uri, array $options);

    public function put($uri, array $options);

    public function get($uri, array $options);

    public function delete($uri, array $options);
}
