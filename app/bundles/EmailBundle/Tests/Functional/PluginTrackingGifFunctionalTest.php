<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use PHPUnit\Framework\Attributes\DataProvider;

final class PluginTrackingGifFunctionalTest extends MauticMysqlTestCase
{
    private const SECRET = 'plugin_tracking_secret';

    private const EMAIL = 'plugin-tracking@example.com';

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function integrationProvider(): iterable
    {
        yield 'Gmail (IntegrationsBundle)' => ['Gmail', 'MauticGmailBundle'];
        yield 'Outlook' => ['Outlook', 'MauticOutlookBundle'];
    }

    #[DataProvider('integrationProvider')]
    public function testSignedRequestTracksTheContact(string $integrationName, string $bundle): void
    {
        $this->createIntegration($integrationName, $bundle);

        $this->requestTrackingGif($integrationName, $this->sign($this->encodeData()));

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Lead::class, $this->em->getRepository(Lead::class)->findOneBy(['email' => self::EMAIL]));
    }

    #[DataProvider('integrationProvider')]
    public function testRequestWithInvalidSignatureIsIgnored(string $integrationName, string $bundle): void
    {
        $this->createIntegration($integrationName, $bundle);

        $this->requestTrackingGif($integrationName, 'invalid');

        $this->assertResponseIsSuccessful();
        $this->assertNotInstanceOf(Lead::class, $this->em->getRepository(Lead::class)->findOneBy(['email' => self::EMAIL]));
    }

    public function testUnknownIntegrationIsIgnored(): void
    {
        $this->requestTrackingGif('Unknown', $this->sign($this->encodeData()));

        $this->assertResponseIsSuccessful();
        $this->assertNotInstanceOf(Lead::class, $this->em->getRepository(Lead::class)->findOneBy(['email' => self::EMAIL]));
    }

    private function createIntegration(string $name, string $bundle): void
    {
        $plugin = new Plugin();
        $plugin->setName($name);
        $plugin->setBundle($bundle);
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName($name);
        $integration->setIsPublished(true);
        $integration->setPlugin($plugin);
        $integration->setApiKeys(['secret' => self::getContainer()->get(EncryptionHelper::class)->encrypt(self::SECRET)]);
        $this->em->persist($integration);

        $this->em->flush();
    }

    private function encodeData(): string
    {
        return base64_encode((string) gzencode(http_build_query([
            'email'   => self::EMAIL,
            'from'    => 'sender@example.com',
            'subject' => 'Subject',
            'body'    => 'Body',
        ])));
    }

    private function sign(string $data): string
    {
        return hash('crc32b', crypt(urlencode($data), '$1$'.self::SECRET));
    }

    private function requestTrackingGif(string $integrationName, string $signature): void
    {
        $this->client->request('GET', '/plugin/'.$integrationName.'/tracking.gif?'.http_build_query([
            'd'   => $this->encodeData(),
            'sig' => $signature,
        ]));
    }
}
