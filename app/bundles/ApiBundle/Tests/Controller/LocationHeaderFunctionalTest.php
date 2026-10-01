<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creating an entity through the API must answer with an absolute URL in Location.
 *
 * generateUrl()'s third argument was a bool until Symfony 2.8 and both of these call sites
 * still passed true. It is a reference type now, where ABSOLUTE_URL is 0, so true cast to 1,
 * ABSOLUTE_PATH, and the header carried a path.
 */
final class LocationHeaderFunctionalTest extends MauticMysqlTestCase
{
    public function testCreatingAnEntityReturnsAnAbsoluteLocation(): void
    {
        $this->client->request(Request::METHOD_POST, '/api/stages/new', ['name' => 'Location header stage']);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string) $response->getContent());

        $this->assertAbsoluteLocation($response->headers->get('Location'));
    }

    public function testCloningACampaignReturnsAnAbsoluteLocation(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Location header campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        $this->client->request(Request::METHOD_POST, sprintf('/api/campaigns/clone/%s', $campaign->getId()));

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $this->assertAbsoluteLocation($response->headers->get('Location'));
    }

    private function assertAbsoluteLocation(?string $location): void
    {
        $this->assertNotNull($location, 'The response must carry a Location header.');
        $this->assertMatchesRegularExpression(
            '#^https?://#',
            $location,
            sprintf('Location must be an absolute URL, got "%s". A path here means generateUrl() was given ABSOLUTE_PATH.', $location)
        );
    }
}
