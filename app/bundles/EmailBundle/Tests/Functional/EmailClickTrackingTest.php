<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailClickTrackingTest extends MauticMysqlTestCase
{
    public function testEmailClick(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.cz');

        $email = new Email();
        $email->setName('Test email');
        $email->setSubject('Test email');
        $email->setCustomHtml('<html><head></head><body>Test email</body></html>');

        $stat = new Stat();
        $stat->setLead($contact);
        $stat->setEmail($email);
        $stat->setEmailAddress('john@doe.cz');
        $stat->setTrackingHash('67167f57a4c05265936091');
        $stat->setDateSent(new \DateTime());

        $page = new Page();
        $page->setTitle('Test page');
        $page->setAlias('test-page');
        $page->setCustomHtml('<html><head></head><body>Test page</body></html>');

        $this->em->persist($contact);
        $this->em->persist($email);
        $this->em->persist($stat);
        $this->em->persist($page);
        $this->em->flush();

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/test-page?&ct=YToxOntzOjQ6InN0YXQiO3M6MjI6IjY3MTY3ZjU3YTRjMDUyNjU5MzYwOTEiO30%3D');
        Assert::assertTrue($this->client->getResponse()->isSuccessful());

        $pageHitRepository = $this->em->getRepository(Hit::class);
        \assert($pageHitRepository instanceof HitRepository);

        $hit = $pageHitRepository->findOneBy(['page' => $page]);
        Assert::assertSame($contact->getId(), $hit->getLead()->getId());
    }
}
