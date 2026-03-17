<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerListingPageTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['email_columns'] = ['name', 'id'];

        parent::setUp();
    }

    public function testEmailListingColumnsCanBeConfigured(): void
    {
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Subject A');
        $email->setEmailType('list');

        $this->em->persist($email);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertCount(1, $crawler->filter('.email-list thead tr th.col-email-name'));
        Assert::assertCount(1, $crawler->filter('.email-list thead tr th.col-email-id'));
        Assert::assertCount(0, $crawler->filter('.email-list thead tr th.col-email-category'));
        Assert::assertCount(0, $crawler->filter('.email-list thead tr th.col-email-dateModified'));
    }
}
