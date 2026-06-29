<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Functional\Entity;

use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;

final class DownloadRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private DownloadRepository $downloadRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var DownloadRepository $repository */
        $repository               = $this->em->getRepository(Download::class);
        $this->downloadRepository = $repository;
    }

    public function testGetDownloadCountsByPageRespectsFromDateFilter(): void
    {
        $page = new Page();
        $page->setTitle('Landing page');
        $page->setAlias('landing-page');
        $page->setHits(77);
        $this->em->persist($page);
        $this->em->flush();

        $this->createDownload('2026-03-10 10:00:00', 200, 'page-track-in', 'page', (int) $page->getId());
        $this->createDownload('2026-02-10 10:00:00', 200, 'page-track-old', 'page', (int) $page->getId());
        $this->createDownload('2026-03-12 10:00:00', 404, 'page-track-wrong-code', 'page', (int) $page->getId());

        $this->em->flush();

        $result = $this->downloadRepository->getDownloadCountsByPage(
            (int) $page->getId(),
            new \DateTime('2026-03-01 00:00:00', new \DateTimeZone('UTC'))
        );

        self::assertArrayHasKey((string) $page->getId(), $result);
        self::assertSame('1', (string) $result[(string) $page->getId()]['count']);
        self::assertSame('Landing page', $result[(string) $page->getId()]['name']);
        self::assertSame('77', (string) $result[(string) $page->getId()]['total']);
    }

    public function testGetDownloadCountsByEmailRespectsFromDateFilter(): void
    {
        $email = new Email();
        $email->setName('AB parent');
        $email->setSubject('AB subject');
        $email->setEmailType('list');
        $email->setVariantSentCount(25);
        $this->em->persist($email);

        $this->createDownload('2026-03-20 11:00:00', 200, 'email-track-in', null, null, $email);
        $this->createDownload('2026-02-20 11:00:00', 200, 'email-track-old', null, null, $email);
        $this->createDownload('2026-03-21 11:00:00', 500, 'email-track-wrong-code', null, null, $email);

        $this->em->flush();

        $result = $this->downloadRepository->getDownloadCountsByEmail(
            (int) $email->getId(),
            new \DateTime('2026-03-01 00:00:00', new \DateTimeZone('UTC'))
        );

        self::assertArrayHasKey((string) $email->getId(), $result);
        self::assertSame('1', (string) $result[(string) $email->getId()]['count']);
        self::assertSame('AB subject', $result[(string) $email->getId()]['name']);
        self::assertSame('25', (string) $result[(string) $email->getId()]['total']);
    }

    private function createDownload(
        string $dateDownload,
        int $code,
        string $trackingId,
        ?string $source,
        ?int $sourceId,
        ?Email $email = null,
    ): void {
        $download = new Download();
        $download->setDateDownload(new \DateTime($dateDownload, new \DateTimeZone('UTC')));
        $download->setCode($code);
        $download->setTrackingId($trackingId);

        if (null !== $source) {
            $download->setSource($source);
        }

        if (null !== $sourceId) {
            $download->setSourceId($sourceId);
        }

        if (null !== $email) {
            $download->setEmail($email);
        }

        $this->em->persist($download);
    }
}
