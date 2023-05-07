# Messenger Bundle

The bundle makes use of Symfony's messenger component to dispatch and handle messages. 

## Transports

It creates `synchronous` transport and declares `failed` transport. Default doctrine setting is `messenger_messages` table
, as default, but you can use whatever transport that suits your needs.

https://symfony.com/doc/current/messenger.html#transports-async-queued-messages

The only thing you need to do is to map the routing key to the transport you wish to use.

https://symfony.com/doc/current/messenger.html#routing-messages-to-a-transport

By default, the transport is set to **synchronous**, meaning no AMQP/Doctrine or whatsoever is used and the request is handled directly.

Here [a sample configuration](#sample-configuration)

## Notifications

Currently, 2 messages are defined.
 * [EmailHitNotification](app/bundles/MessengerBundle/Message/EmailHitNotification.php)
 * [PageHitNotification](app/bundles/MessengerBundle/Message/PageHitNotification.php)



### Sample configuration
```php
$container->loadFromExtension('framework', [
    'routing' => [
        PageHitNotification::class  => MauticMessengerRoutes::SYNC,
        EmailHitNotification::class => MauticMessengerRoutes::EMAIL_HIT,
    ],
    'messenger' => [
        'failure_transport' => 'failed', // Define other than default if you wish
        'transports'        => [
            MauticMessengerRoutes::SYNC      => 'sync://',
            MauticMessengerRoutes::PAGE_HIT  => [
                'dsn'     => '%MESSENGER_TRANSPORT_DSN%',
                'options' => [
                    'heartbeat'  => 1,
                    'persistent' => true,
                    'vhost'      => '/',
                    'exchange'   => [
                        'name'                        => 'my_exchange',
                        'type'                        => 'direct',
                        'default_publish_routing_key' => 'page_hit',
                    ],
                    'queues'     => [
                        'page_hit' => [
                            'binding_keys' => ['page_hit'],
                        ],
                    ],
                ],

                'retry_strategy' => [
                    'max_retries' => 5,
                    'delay'       => 500,
                    'multiplier'  => 3,
                    'max_delay'   => 0,
                ],
            ],
        ],
],
```
where `MauticMessengerRoutes::PAGE_HIT` is the transport name and can be changed to whatever you wish as long as it is mapped in parameters

## Usage

to run consumer, simply 
```shell
sudo -uwww-data bin/console messenger:consume page_hit email_hit
```
