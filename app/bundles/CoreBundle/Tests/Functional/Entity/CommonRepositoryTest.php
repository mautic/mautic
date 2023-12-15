<?php

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class CommonRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @testdox Test that is:mine does not throw an exception due to bad DQL
     */
    public function testIsMineSearchCommandDoesntCauseExceptionDueToBadDQL(): void
    {
        $this->client->request('GET', 's/contacts?search=is:mine');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('is:mine', $this->client->getResponse()->getContent());
    }

    public function testIsMineSearchCommandDoesntCauseExceptionDueToBadDQLForCompanies(): void
    {
        $this->client->request('GET', 's/companies?search=is:mine');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('is:mine', $this->client->getResponse()->getContent());
    }

    public function testIsPublishedSearchCommandDoesntCauseExceptionDueToBadDQLForEmails(): void
    {
        $this->client->request('GET', 's/emails?search=is:published');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('is:published', $this->client->getResponse()->getContent());
    }
}
