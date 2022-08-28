<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\AssetBundle\EventListener\DetermineWinnerSubscriber;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

class DetermineWinnerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EntityManagerInterface
     */
    private $em;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var DetermineWinnerSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->subscriber = new DetermineWinnerSubscriber($this->em, $this->translator);
    }

    public function testOnDetermineDownloadRateWinner()
    {
        $parentMock    = $this->createMock(Page::class);
        $childMock     = $this->createMock(Page::class);
        $children      = [2 => $childMock];
        $repoMock      = $this->createMock(DownloadRepository::class);
        $parameters    = ['parent' => $parentMock, 'children' => $children];
        $event         = new DetermineWinnerEvent($parameters);
        $startDate     = new \DateTime();

        $transDownloads = 'downloads';
        $transHits      = 'hits';

        $counts = [
            1 => [
                'count' => 20,
                'id'    => 1,
                'name'  => 'Test 5',
                'total' => 100,
                ],
            2 => [
                'count' => 25,
                'id'    => 2,
                'name'  => 'Test 6',
                'total' => 150,
                ],
        ];

        $this->translator->method('trans')
            ->willReturnOnConsecutiveCalls($transDownloads, $transHits);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repoMock);

        $parentMock->expects($this->any())
            ->method('isPublished')
            ->willReturn(true);

        $childMock->expects($this->any())
            ->method('isPublished')
            ->willReturn(true);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $childMock->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $repoMock->expects($this->once())
            ->method('getDownloadCountsByPage')
            ->with([1, 2], $startDate)
            ->willReturn($counts);

        $this->subscriber->onDetermineDownloadRateWinner($event);

        $expectedData = [
            $transDownloads => [$counts[1]['count'], $counts[2]['count']],
            $transHits      => [$counts[1]['total'], $counts[2]['total']],
         ];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [1]);
        $this->assertEquals($abTestResults['support']['data'], $expectedData);
    }
}
