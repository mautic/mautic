<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Security;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class LeadPermissionsFunctionalTest extends MauticMysqlTestCase
{
    public function testRolePageForPermissionAvailability(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Contacts - User has access to', $content);
        $this->assertStringContainsString('Segments - User has access to', $content);
        $this->assertStringContainsString('Custom Fields - User has access to', $content);
        $this->assertStringContainsString('Import - User has access to', $content);

        $leadPermissionTab = $crawler->filter('#leadPermissionTab');
        $this->assertCount(1, $leadPermissionTab->count());

        $leadsRole = $crawler->filter('input[name="role[permissions][lead:leads][]"]');
        $this->assertCount(8, $leadsRole->count());

        $listsRole = $crawler->filter('input[name="role[permissions][lead:lists][]"]');
        $this->assertCount(8, $listsRole->count());

        $fieldsRole = $crawler->filter('input[name="role[permissions][lead:fields][]"]');
        $this->assertCount(1, $fieldsRole->count());

        $importsRole = $crawler->filter('input[name="role[permissions][lead:imports][]"]');
        $this->assertCount(6, $importsRole->count());
    }
}
