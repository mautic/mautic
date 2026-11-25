<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\OidcSubjectId;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

final class ConfigSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private const CLIENT_URL = 'https://idp-a.example.com/realms/main';

    protected function setUp(): void
    {
        $this->configParams['open_id_client_url']    = self::CLIENT_URL;
        $this->configParams['open_id_mapping_field'] = 'sub';

        parent::setUp();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->em->persist(new OidcSubjectId($user, 'admin-subject'));
        $this->em->flush();
    }

    public function testSavingUnchangedProviderKeepsOidcLinks(): void
    {
        $this->saveUserConfig([]);

        $this->assertSame(1, $this->countOidcLinks());
    }

    public function testChangingProviderUrlDeletesOidcLinks(): void
    {
        $this->saveUserConfig(['config[userconfig][open_id_client_url]' => 'https://idp-b.example.com/realms/main']);

        $this->assertSame(0, $this->countOidcLinks());
    }

    /**
     * @param array<string, string> $values
     */
    private function saveUserConfig(array $values): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit/userconfig');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('config[buttons][apply]')->form();
        $form->setValues($values);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Configuration successfully updated', (string) $this->client->getResponse()->getContent());
    }

    private function countOidcLinks(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.OidcSubjectId::TABLE_NAME);
    }
}
