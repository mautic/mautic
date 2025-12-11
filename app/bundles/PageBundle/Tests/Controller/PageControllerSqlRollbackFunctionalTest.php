<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PageControllerSqlRollbackFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testRedirectNotPersistClickthrough(): void
    {
        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $this->em->persist($lead);
        $this->em->flush();

        $redirectUrl  = 'https://mautic.org/';
        $redirect     = new Redirect();
        $redirectHash = uniqid('', true);
        $redirect->setRedirectId($redirectHash);
        $redirect->setUrl($redirectUrl);
        $this->em->persist($redirect);
        $this->em->flush();

        $email = new Email();
        $email->setName('Test email');
        $this->em->persist($email);
        $this->em->flush();

        $statHash = uniqid('', true);
        $stat     = new Stat();
        $stat->setEmail($email);
        $stat->setEmailAddress($lead->getEmail());
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setTrackingHash($statHash);
        $this->em->persist($stat);
        $this->em->flush();

        $ct = [
            'source'  => ['email', $email->getId()],
            'email'   => $email->getId(),
            'stat'    => $statHash,
            'lead'    => '1',
            'channel' => ['email' => $email->getId()],
        ];
        $encodedCt = base64_encode(serialize($ct));

        $this->setUpSymfony($this->configParams);
        $this->client->followRedirects(false);

        $this->client->request(Request::METHOD_GET, "/r/{$redirectHash}?ct={$encodedCt}");
        $response = $this->client->getResponse();

        Assert::assertTrue($response->isRedirect($redirectUrl), (string) $response);

        // Re-enable redirect following for subsequent tests.
        $this->client->followRedirects();

        $hitRepository = $this->em->getRepository(Hit::class);
        /** @var Hit|null $hit */
        $hit = $hitRepository->findOneBy(['lead' => $lead]);

        Assert::assertNotNull($hit, 'A Hit entity should have been created.');
        Assert::assertSame('email', $hit->getSource(), 'The hit source should be email.');
        Assert::assertSame($email->getId(), $hit->getSourceId(), 'The hit source ID should be the email ID.');
        Assert::assertSame($redirect->getId(), $hit->getRedirect()->getId(), 'The hit should be associated with the correct redirect.');
    }
}
