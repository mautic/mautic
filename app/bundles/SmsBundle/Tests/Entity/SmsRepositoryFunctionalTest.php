<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;

final class SmsRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private SmsRepository $smsRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $smsRepository = $this->em->getRepository(Sms::class);
        $this->assertInstanceOf(SmsRepository::class, $smsRepository);
        $this->smsRepository = $smsRepository;
    }

    public function testPublishedBroadcastDiscoveryHonorsTheScheduleWindowAndExplicitId(): void
    {
        $activeOneTime = $this->createSms(
            'active-one-time',
            new \DateTime('-1 hour'),
        );
        $activeContinuing = $this->createSms(
            'active-continuing',
            new \DateTime('-1 hour'),
            new \DateTime('+1 hour'),
            true,
        );
        $beforeStart = $this->createSms(
            'before-start',
            new \DateTime('+1 hour'),
        );
        $afterStop = $this->createSms(
            'after-stop',
            new \DateTime('-2 hours'),
            new \DateTime('-1 hour'),
            true,
        );
        $cancelled = $this->createSms('cancelled', null);
        $unpublished = $this->createSms(
            'unpublished',
            new \DateTime('-1 hour'),
            isPublished: false,
        );
        $template = $this->createSms(
            'template',
            new \DateTime('-1 hour'),
            smsType: 'template',
        );
        $this->em->flush();

        $discoveredIds = $this->getIds($this->smsRepository->getPublishedBroadcastsIterable());

        $this->assertContains($activeOneTime->getId(), $discoveredIds);
        $this->assertContains($activeContinuing->getId(), $discoveredIds);
        foreach ([$beforeStart, $afterStop, $cancelled, $unpublished, $template] as $notDiscoverable) {
            $this->assertNotContains($notDiscoverable->getId(), $discoveredIds);
        }

        $this->assertSame(
            [$activeOneTime->getId()],
            $this->getIds($this->smsRepository->getPublishedBroadcastsIterable($activeOneTime->getId())),
        );
        $this->assertSame(
            [$activeContinuing->getId()],
            $this->getIds($this->smsRepository->getPublishedBroadcastsIterable($activeContinuing->getId())),
        );
        foreach ([$beforeStart, $afterStop, $cancelled, $unpublished, $template] as $notDiscoverable) {
            $this->assertSame(
                [],
                $this->getIds($this->smsRepository->getPublishedBroadcastsIterable($notDiscoverable->getId())),
            );
        }
    }

    private function createSms(
        string $name,
        ?\DateTimeInterface $publishUp,
        ?\DateTimeInterface $publishDown = null,
        bool $continueSending = false,
        bool $isPublished = true,
        string $smsType = 'list',
    ): Sms {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage('Scheduled broadcast');
        $sms->setSmsType($smsType);
        $sms->setContinueSending($continueSending);
        $sms->setPublishUp($publishUp);
        $sms->setPublishDown($publishDown);
        $sms->setIsPublished($isPublished);
        $this->em->persist($sms);

        return $sms;
    }

    /**
     * @param iterable<Sms> $smses
     *
     * @return int[]
     */
    private function getIds(iterable $smses): array
    {
        $ids = [];
        foreach ($smses as $sms) {
            $ids[] = (int) $sms->getId();
        }

        return $ids;
    }
}
