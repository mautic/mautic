<?php

declare(strict_types=1);

return [
    'name'        => 'Send Example API',
    'description' => 'Exposes the "Send example" email action (EmailModel::sendSampleEmailToUser) as a REST API endpoint: POST /api/emails/{id}/example/send',
    'version'     => '1.0.0',
    'author'      => 'Evrima',
    'routes'      => [
        'main'   => [],
        'public' => [],
        'api'    => [
            'plugin_sendexampleapi_send' => [
                'path'       => '/emails/{id}/example/send',
                'controller' => 'MauticPlugin\MauticSendExampleApiBundle\Controller\Api\ExampleSendApiController::sendExampleAction',
                'method'     => 'POST',
            ],
        ],
    ],
    'services' => [],
    'menu'     => [],
];
