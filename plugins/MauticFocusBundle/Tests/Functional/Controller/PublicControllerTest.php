<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\IsolatedTestTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Redirect;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class PublicControllerTest extends MauticMysqlTestCase
{
    use IsolatedTestTrait;

    public function testCheckActionReturnsNoContentWhenNoFilterBasedItemsExist(): void
    {
        // A published item without filters must not be served by the check endpoint
        $this->createFocusItem('No filters', []);

        $this->client->request(Request::METHOD_GET, '/focus/check');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertSame('', $this->client->getResponse()->getContent());
    }

    public function testCheckActionReturnsMatchingItemsForTrackedContactOnly(): void
    {
        $focus = $this->createFocusItem('Filter based', [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'operator' => '=',
                'filter'   => 'focus-match@example.com',
                'display'  => null,
            ],
        ]);

        $matchingLead = $this->createLeadWithTrackingStat('focus-match@example.com', 'focus-tracking-hash-1');
        $this->createLeadWithTrackingStat('focus-other@example.com', 'focus-tracking-hash-2');

        $leadCount   = $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'leads');
        $deviceCount = $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'lead_devices');

        // Matching contact gets the focus item
        $ct = ClickthroughHelper::encodeArrayForUrl(['stat' => 'focus-tracking-hash-1']);
        $this->client->request(Request::METHOD_GET, '/focus/check?ct='.$ct);
        $response = $this->client->getResponse();

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame($focus->getId(), $payload['focus_items'][0]['id']);
        $this->assertStringContainsString(sprintf('/focus/%d.js', $focus->getId()), (string) $payload['focus_items'][0]['js_url']);
        $this->assertSame($matchingLead->getId(), $payload['id']);
        $this->assertArrayHasKey('device_id', $payload);

        // Non-matching contact gets nothing
        $ct = ClickthroughHelper::encodeArrayForUrl(['stat' => 'focus-tracking-hash-2']);
        $this->client->request(Request::METHOD_GET, '/focus/check?ct='.$ct);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // The endpoint is non-trackable: it must not have created leads or devices
        $this->assertSame($leadCount, $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'leads'));
        $this->assertSame($deviceCount, $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'lead_devices'));
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    private function createFocusItem(string $name, array $filters): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('notice');
        $focus->setStyle('modal');
        $focus->setIsPublished(true);
        $focus->setFilters($filters);
        $this->em->persist($focus);
        $this->em->flush();

        return $focus;
    }

    private function createLeadWithTrackingStat(string $email, string $trackingHash): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        $stat = new Stat();
        $stat->setLead($lead);
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress($email);
        $stat->setDateSent(new \DateTime());
        $this->em->persist($stat);

        $this->em->flush();

        return $lead;
    }

    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGenerateActionWithContactTokenInLinkUrl(): void
    {
        $linkUrl = 'https://{contactfield=site_url}/tour';
        $focus   = new Focus();
        $focus->setName('Test');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'headline'        => '',
                'link_text'       => 'Link text',
                'link_url'        => $linkUrl,
                'font'            => 'Arial, Helvetica, sans-serif',
                'link_new_window' => 1,
            ],
            'when'  => 'immediately',
            'modal' => [
                'placement' => 'top',
            ],
            'frequency' => 'everypage',
            'colors'    => [
                'primary'     => '#4e5d9d',
                'text'        => '#000000',
                'button'      => '#fdb933',
                'button_text' => '#ffffff',
            ],
        ]);
        $this->em->persist($focus);
        $this->em->flush();
        $this->em->clear();

        $this->client->request(Request::METHOD_GET, sprintf('/focus/%s.js', $focus->getId()));
        $content = $this->client->getResponse()->getContent();

        $redirects = $this->em->getRepository(Redirect::class)->findAll();
        $this->assertCount(1, $redirects);

        /** @var Redirect $redirect */
        $redirect = reset($redirects);
        $this->assertSame($linkUrl, $redirect->getUrl());

        $url  = $this->router->generate('mautic_url_redirect', ['redirectId' => $redirect->getRedirectId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $twig = $this->getContainer()->get('twig');
        if (!$twig->hasExtension(\Twig\Extension\EscaperExtension::class)) {
            $twig->addExtension(new \Twig\Extension\EscaperExtension());
        }
        $url = $twig->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($url, 'js');
        $this->assertStringContainsString($url, (string) $content);
    }
}
