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
        $this->assertEquals(1, $leadPermissionTab->count());

        $leadsRole = $crawler->filter('input[name="role[permissions][lead:leads][]"]');
        $this->assertEquals(11, $leadsRole->count());
        $this->assertContains('viewsamerole', $leadsRole->extract(['value']));
        $this->assertContains('editsamerole', $leadsRole->extract(['value']));
        $this->assertContains('deletesamerole', $leadsRole->extract(['value']));

        $listsRole = $crawler->filter('input[name="role[permissions][lead:lists][]"]');
        $this->assertEquals(14, $listsRole->count());
        $this->assertContains('viewsamerole', $listsRole->extract(['value']));
        $this->assertContains('editsamerole', $listsRole->extract(['value']));
        $this->assertContains('deletesamerole', $listsRole->extract(['value']));
        $this->assertContains('publishsamerole', $listsRole->extract(['value']));

        $fieldsRole = $crawler->filter('input[name="role[permissions][lead:fields][]"]');
        $this->assertEquals(2, $fieldsRole->count());

        $importsRole = $crawler->filter('input[name="role[permissions][lead:imports][]"]');
        $this->assertEquals(6, $importsRole->count());
    }
}
