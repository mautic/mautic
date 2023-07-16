# Messenger Bundle

The bundle makes use of [Symfony's messenger component](https://symfony.com/doc/5.4/messenger.html) to dispatch and handle messages. 

## Transports

It creates `synchronous` transport only, it is necessary for processing in case external messenger is not used. 

https://symfony.com/doc/current/messenger.html#transports-async-queued-messages

The only thing you need to do is to map the routing key to the transport you wish to use.

https://symfony.com/doc/current/messenger.html#routing-messages-to-a-transport

By default, the transport is set to **synchronous**, meaning no AMQP/Doctrine or whatsoever is used and the request is handled directly and the message is marked as synchronous process if it implements **RequestStatusInterface**.

[Currently defined routes](MauticMessengerRoutes.php) are SYNC, PAGE_HIT, EMAIL_HIT although in default configuration only the SYNC is used.

> https://symfony.com/doc/5.4/messenger.html#routing-messages-to-a-transport

Here [a sample configuration](#sample-configuration)

## Notifications

Currently, 2 messages are defined.
 * [EmailHitNotification](app/bundles/MessengerBundle/Message/EmailHitNotification.php)
 * [PageHitNotification](app/bundles/MessengerBundle/Message/PageHitNotification.php)

configuring transports:


### Sample configuration
I believe the best place to configure the messenger is `app/config/config_local.php` or your own bundle.

```php
<?php # app/config/config_local.php

$container->loadFromExtension('framework', [
    'messenger' => [
        'routing'   => [
            \Mautic\MessengerBundle\Message\PageHitNotification::class  => \Mautic\MessengerBundle\MauticMessengerTransports::HIT,
            \Mautic\MessengerBundle\Message\EmailHitNotification::class => \Mautic\MessengerBundle\MauticMessengerTransports::HIT,
        ],
        'failure_transport' => 'failed', // Define other than default if you wish
        'transports' => [
            'failed' => [
                'dsn' => 'doctrine://default?queue_name=failed',
            ],
            \Mautic\MessengerBundle\MauticMessengerTransports::SYNC      => 'sync://',
            \Mautic\MessengerBundle\MauticMessengerTransports::HIT => [
                'dsn'            => '%env(MAUTIC_MESSENGER_TRANSPORT_DSN)%',
                'serializer'     => 'messenger.transport.jms_serializer',
                'options'        => [
                    'heartbeat'  => 1,
                    'persistent' => true,
                    'vhost'      => '/',
                    'exchange'   => [
                        'name'                        => 'mautic',
                        'type'                        => 'direct',
                        'default_publish_routing_key' => 'hit',
                    ],
                    'queues'     => [
                        'email_hit' => [
                            'binding_keys' => ['hit'],
                            'arguments'    => [
                                'x-expires' => 60 * 60 * 24 * 21 * 1000, // queue ttl without consumer using it
                            ],
                        ],
                    ],
                ],
                'serializer'     => 'messenger.transport.native_php_serializer',
                'retry_strategy' => [
                    'max_retries' => 3,
                    'delay'       => 500,
                    'multiplier'  => 3,
                    'max_delay'   => 0,
                ],
            ],
        ],
    ],
]);
```
## Usage

to run consumer, simply 
```shell
sudo -uwww-data bin/console messenger:consume page_hit email_hit
```
