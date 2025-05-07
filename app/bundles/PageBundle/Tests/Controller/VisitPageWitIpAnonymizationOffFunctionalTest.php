<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class VisitPageWitIpAnonymizationOffFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['anonymize_ip'] = false;

        parent::setUp();
    }

    public function testPageWithIpAnonymizationOff(): void
    {
        // create landing page
        $pageObject = new Page();
        $pageObject->setIsPublished(true);
        $pageObject->setDateAdded(new \DateTime());
        $pageObject->setTitle('Page:Page:Anonymization:Off');
        $pageObject->setAlias('page-page-anonymizaiton-off');
        $pageObject->setTemplate('Blank');
        $pageObject->setCustomHtml('Test Html');
        $pageObject->setLanguage('en');
        $this->em->persist($pageObject);
        $this->em->flush();

        $this->logoutUser();
        $pageContent = $this->client->request(Request::METHOD_GET, '/page-page-anonymizaiton-off');

        Assert::assertTrue($this->client->getResponse()->isOk(), $pageContent->text());
        Assert::assertStringContainsString('Test Html', $pageContent->text());

        /** @var HitRepository $hitRepository */
        $hitRepository = $this->em->getRepository(Hit::class);

        /** @var Hit[] $hits */
        $hits = $hitRepository->findBy(['page' => $pageObject->getId()]);
        Assert::assertCount(1, $hits);
        Assert::assertSame('127.0.0.1', $hits[0]->getIpAddress()->getIpAddress());
    }
}
