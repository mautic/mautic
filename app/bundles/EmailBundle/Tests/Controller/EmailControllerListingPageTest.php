<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerListingPageTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['email_columns'] = match ($this->name()) {
            'testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreEmpty'   => [],
            'testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreInvalid' => ['does_not_exist'],
            default                                                                    => ['name', 'id'],
        };

        parent::setUp();
    }

    public function testEmailListingColumnsCanBeConfigured(): void
    {
        $this->createEmail();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-category'));
        $this->assertCount(0, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
    }

    public function testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreEmpty(): void
    {
        $this->assertDefaultColumnsAreRendered();
    }

    public function testEmailListingFallsBackToDefaultColumnsWhenConfiguredColumnsAreInvalid(): void
    {
        $this->assertDefaultColumnsAreRendered();
    }

    private function assertDefaultColumnsAreRendered(): void
    {
        $this->createEmail();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-category'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-template'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-stats'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-dateAdded'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-createdByUser'));
        $this->assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
    }

    private function createEmail(): void
    {
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Subject A');
        $email->setEmailType('list');

        $this->em->persist($email);
        $this->em->flush();
    }
}
