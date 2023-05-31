<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class CampaignApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testCreateNewCampaign(): void
    {
        $segment = new LeadList();
        $segment->setName('test');
        $segment->setAlias('test');
        $segment->setPublicName('test');

        $email = new Email();
        $email->setName('test');
        $email->setSubject('test');
        $email->setCustomHtml('test');

        $this->em->persist($segment);
        $this->em->flush();

        $payload = [
            'name'        => 'test',
            'description' => 'Created via API',
            'events'      => [
                [
                    'id'          => 'new_44', // Event ID will be replaced on /new
                    'name'        => 'Send email',
                    'description' => 'API test',
                    'type'        => 'email.send',
                    'eventType'   => 'action',
                    'order'       => 1,
                    'properties'  => [
                        'email'      => $email->getId(),
                        'email_type' => 'transactional',
                    ],
                    'triggerInterval'     => 1,
                    'triggerIntervalUnit' => 'd',
                    'triggerMode'         => 'interval',
                    'children'            => [],
                    'parent'              => null,
                    'decisionPath'        => 'yes',
                ],
            ],
            'forms' => [],
            'lists' => [
                [
                    'id' => $segment->getId(),
                ],
            ],
            'canvasSettings' => [
                'nodes' => [
                    [
                        'id'        => 'new_44', // Event ID will be replaced on /new
                        'positionX' => '650',
                        'positionY' => '189',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '629',
                        'positionY' => '65',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_44', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, 'api/campaigns/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        Assert::assertEquals($payload['name'], $response['campaign']['name']);
        Assert::assertEquals($payload['description'], $response['campaign']['description']);
        Assert::assertEquals($payload['events'][0]['name'], $response['campaign']['events'][0]['name']);
        Assert::assertEquals($segment->getId(), $response['campaign']['lists'][0]['id']);
    }
}
