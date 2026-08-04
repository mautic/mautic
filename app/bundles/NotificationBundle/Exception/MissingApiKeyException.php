<?php

namespace Mautic\NotificationBundle\Exception;

final class MissingApiKeyException extends \Exception
{
    protected $message = 'Missing Notification API Key';
}
