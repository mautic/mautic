<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Security;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('non-parallel')]
final class LeadPermissionsFunctionalTest extends MauticMysqlTestCase
{
    public function testRolePageForPermissionAvailability(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Contacts - User has access to', (string) $content);
        $this->assertStringContainsString('Notes - User has access to', (string) $content);
        $this->assertStringContainsString('Segments - User has access to', (string) $content);
        $this->assertStringContainsString('Custom Fields - User has access to', (string) $content);
        $this->assertStringContainsString('Import - User has access to', (string) $content);

        $leadPermissionTab = $crawler->filter('#leadPermissionTab');
        $this->assertCount(1, $leadPermissionTab);

        $leadsRole = $crawler->filter('input[name="role[permissions][lead:leads][]"]');
        $this->assertCount(8, $leadsRole);

        $notesRole = $crawler->filter('input[name="role[permissions][lead:notes][]"]');
        $this->assertCount(8, $notesRole);

        $listsRole = $crawler->filter('input[name="role[permissions][lead:lists][]"]');
        $this->assertCount(10, $listsRole);

        $fieldsRole = $crawler->filter('input[name="role[permissions][lead:fields][]"]');
        $this->assertCount(2, $fieldsRole);

        $importsRole = $crawler->filter('input[name="role[permissions][lead:imports][]"]');
        $this->assertCount(6, $importsRole);
    }
}
