<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Symfony\Component\HttpFoundation\Response;

class EmailApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCategoryData::class]);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    public function testSingleEmailWorkflow()
    {
        // Create a couple of segments first:
        $payload = [
            [
                'name'        => 'API segment A',
                'description' => 'Segment created via API test',
            ],
            [
                'name'        => 'API segment B',
                'description' => 'Segment created via API test',
            ],
        ];

        $this->client->request('POST', '/api/segments/batch/new', $payload);
        $clientResponse  = $this->client->getResponse();
        $segmentResponse = json_decode($clientResponse->getContent(), true);
        $segmentAId      = $segmentResponse['lists'][0]['id'];
        $segmentBId      = $segmentResponse['lists'][1]['id'];

        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertGreaterThan(0, $segmentAId);

        // Create email with the new segment:
        $payload = [
            'name'       => 'API email',
            'subject'    => 'Email created via API test',
            'emailType'  => 'list',
            'lists'      => [$segmentAId],
            'customHtml' => '<h1>Email content created by an API test</h1>',
        ];

        $this->client->request('POST', '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $emailId        = $response['email']['id'];

        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertGreaterThan(0, $emailId);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertCount(1, $response['email']['lists']);
        $this->assertEquals($segmentAId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Edit PATCH:
        $patchPayload = [
            'name'  => 'API email renamed',
            'lists' => [$segmentBId],
        ];
        $this->client->request('PATCH', "/api/emails/{$emailId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals('API email renamed', $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(1, $response['email']['lists']);
        $this->assertEquals($segmentBId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Edit PUT:
        $payload['subject'] .= ' renamed';
        $payload['lists']    = [$segmentAId, $segmentBId];
        $payload['language'] = 'en'; // Must be present for PUT as all empty values are being cleared.
        $this->client->request('PUT', "/api/emails/{$emailId}/edit", $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals('Email created via API test renamed', $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($segmentAId, $response['email']['lists'][1]['id']);
        $this->assertEquals($segmentBId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Get:
        $this->client->request('GET', "/api/emails/{$emailId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Delete:
        $this->client->request('DELETE', "/api/emails/{$emailId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertNull($response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/emails/{$emailId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame(404, $response['errors'][0]['code']);

        // Delete also testing segments:
        $this->client->request('DELETE', "/api/segments/batch/delete?ids={$segmentAId},{$segmentBId}", []);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Response should include the two entities that we just deleted
        $this->assertSame(2, count($response['lists']));
        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testReplyActionIfNotFound()
    {
        $trackingHash = 'tracking_hash_123';

        // Create new email reply.
        $this->client->request('POST', "/api/emails/reply/{$trackingHash}");
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('Email Stat with tracking hash tracking_hash_123 was not found', $responseData['errors'][0]['message']);
    }

    public function testReplyAction(): void
    {
        $trackingHash = 'tracking_hash_123';

        /** @var StatRepository $statRepository */
        $statRepository = self::$container->get('mautic.email.repository.stat');

        // Create a test email stat.
        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateSent(new \DateTime());

        $statRepository->saveEntity($stat);

        // Create new email reply.
        $this->client->request('POST', "/api/emails/reply/{$trackingHash}");
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame(['success' => true], json_decode($response->getContent(), true));

        // Get the email reply that was just created from the stat API.
        $statReplyQuery = ['where' => [['col' => 'stat_id', 'expr' => 'eq', 'val' => $stat->getId()]]];
        $this->client->request('GET', '/api/stats/email_stat_replies', $statReplyQuery);
        $fetchedReplyData = json_decode($this->client->getResponse()->getContent(), true);

        // Check that the email reply was created correctly.
        $this->assertSame('1', $fetchedReplyData['total']);
        $this->assertSame($stat->getId(), $fetchedReplyData['stats'][0]['stat_id']);
        $this->assertMatchesRegularExpression('/api-[a-z0-9]*/', $fetchedReplyData['stats'][0]['message_id']);

        // Get the email stat that was just updated from the stat API.
        $statQuery = ['where' => [['col' => 'id', 'expr' => 'eq', 'val' => $stat->getId()]]];
        $this->client->request('GET', '/api/stats/email_stats', $statQuery);
        $fetchedStatData = json_decode($this->client->getResponse()->getContent(), true);

        // Check that the email stat was updated correctly/
        $this->assertSame('1', $fetchedStatData['total']);
        $this->assertSame($stat->getId(), $fetchedStatData['stats'][0]['id']);
        $this->assertSame('1', $fetchedStatData['stats'][0]['is_read']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/', $fetchedStatData['stats'][0]['date_read']);
    }
}
