<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Company;
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

        $dwc = new DynamicContent();
        $dwc->setName('test');
        $dwc->setSlotName('test');
        $dwc->setContent('test');

        $company = new Company();
        $company->setName('test');

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->persist($dwc);
        $this->em->persist($company);
        $this->em->flush();

        $payload = [
            'name'        => 'test',
            'description' => 'Created via API',
            'events'      => [
                [
                    'id'          => 'new_43', // Event ID will be replaced on /new
                    'name'        => 'DWC event test',
                    'description' => 'API test',
                    'type'        => 'dwc.decision',
                    'eventType'   => 'decision',
                    'order'       => 1,
                    'properties'  => [
                        'dwc_slot_name'  => 'test',
                        'dynamicContent' => $dwc->getId(),
                    ],
                    'triggerInterval'     => 0,
                    'triggerIntervalUnit' => null,
                    'triggerMode'         => null,
                    'children'            => [
                        'new_44', // Event ID will be replaced on /new
                        'new_55', // Event ID will be replaced on /new
                    ],
                    'parent'       => null,
                    'decisionPath' => null,
                ],
                [
                    'id'          => 'new_44', // Event ID will be replaced on /new
                    'name'        => 'Send email',
                    'description' => 'API test',
                    'type'        => 'email.send',
                    'eventType'   => 'action',
                    'order'       => 2,
                    'properties'  => [
                        'email'      => $email->getId(),
                        'email_type' => 'transactional',
                    ],
                    'triggerInterval'     => 1,
                    'triggerIntervalUnit' => 'd',
                    'triggerMode'         => 'interval',
                    'children'            => [],
                    'parent'              => 'new_43', // Event ID will be replaced on /new
                    'decisionPath'        => 'yes',
                ],
                [
                    'id'          => 'new_55', // Event ID will be replaced on /new
                    'name'        => 'Add to company action',
                    'description' => 'API test',
                    'type'        => 'lead.addtocompany',
                    'eventType'   => 'action',
                    'order'       => 2,
                    'properties'  => [
                        'company' => $company->getId(),
                    ],
                    // 'triggerDate'         => null,
                    'triggerInterval'     => 1,
                    'triggerIntervalUnit' => 'd',
                    'triggerMode'         => 'interval',
                    'children'            => [],
                    'parent'              => 'new_43', // Event ID will be replaced on /new
                    'decisionPath'        => 'no',
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
                        'id'        => 'new_43', // Event ID will be replaced on /new
                        'positionX' => '650',
                        'positionY' => '189',
                    ],
                    [
                        'id'        => 'new_44', // Event ID will be replaced on /new
                        'positionX' => '433',
                        'positionY' => '348',
                    ],
                    [
                        'id'        => 'new_55', // Event ID will be replaced on /new
                        'positionX' => '750',
                        'positionY' => '411',
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
                        'targetId' => 'new_43', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'new_43', // / Event ID will be replaced on /new
                        'targetId' => 'new_44', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'yes',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'new_43', // Event ID will be replaced on /new
                        'targetId' => 'new_55', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'no',
                            'target' => 'top',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, 'api/campaigns/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response   = json_decode($clientResponse->getContent(), true);
        $campaignId = $response['campaign']['id'];
        Assert::assertGreaterThan(0, $campaignId);
        Assert::assertEquals($payload['name'], $response['campaign']['name']);
        Assert::assertEquals($payload['description'], $response['campaign']['description']);
        Assert::assertEquals($payload['events'][0]['name'], $response['campaign']['events'][0]['name']);
        Assert::assertEquals($segment->getId(), $response['campaign']['lists'][0]['id']);

        // Search for this campaign:
        $this->client->request(Request::METHOD_GET, "/api/campaigns?search=ids:{$response['campaign']['id']}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        Assert::assertEquals($payload['name'], $response['campaigns'][$campaignId]['name'], $clientResponse->getContent());
        Assert::assertEquals($payload['description'], $response['campaigns'][$campaignId]['description'], $clientResponse->getContent());
        Assert::assertEquals($payload['events'][0]['name'], $response['campaigns'][$campaignId]['events'][0]['name'], $clientResponse->getContent());
        Assert::assertEquals($segment->getId(), $response['campaigns'][$campaignId]['lists'][0]['id'], $clientResponse->getContent());
    }
}
