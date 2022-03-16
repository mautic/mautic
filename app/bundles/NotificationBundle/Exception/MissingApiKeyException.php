<?php

namespace Mautic\NotificationBundle\Exception;

class MissingApiKeyException extends \Exception
{
    protected $message = 'Missing Notification API Key';
}
