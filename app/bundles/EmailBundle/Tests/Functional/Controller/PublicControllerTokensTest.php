<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Copy;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class PublicControllerTokensTest extends MauticMysqlTestCase
{
    private const SUBJECT = 'Test email';

    public function testViewInBrowserRendersTokensStoredOnStatData(): void
    {
        $email = new Email();
        $email->setName(self::SUBJECT);
        $email->setSubject(self::SUBJECT);
        $email->setCustomHtml('<html><head></head><body>Hi {contactfield=firstname}</body></html>');

        $copy = new Copy();
        $copy->setId('test-copy-body-fixture-000000000');
        $copy->setDateCreated(new \DateTime());
        $copy->setSubject(self::SUBJECT);
        $copy->setBody('<html><head></head><body>Hi {contactfield=firstname}</body></html>');

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setEmailAddress('john@doe.cz');
        $stat->setTrackingHash('abc123trackinghash');
        $stat->setDateSent(new \DateTime());
        $stat->setStoredCopy($copy);
        $stat->setTokens(['{contactfield=firstname}' => 'John']);

        $this->em->persist($email);
        $this->em->persist($copy);
        $this->em->persist($stat);
        $this->em->persist($stat->getData());
        $this->em->flush();

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/email/view/abc123trackinghash');
        $response = $this->client->getResponse();

        Assert::assertTrue($response->isSuccessful(), (string) $response->getContent());
        Assert::assertStringContainsString('Hi John', (string) $response->getContent());
    }
}
