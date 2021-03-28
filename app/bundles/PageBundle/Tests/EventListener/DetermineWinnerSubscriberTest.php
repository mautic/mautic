<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\EventListener\DetermineWinnerSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

class DetermineWinnerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|HitRepository
     */
    private $hitRepository;

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

        $this->hitRepository = $this->createMock(HitRepository::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->subscriber    = new DetermineWinnerSubscriber($this->hitRepository, $this->translator);
    }

    public function testOnDetermineBounceRateWinner()
    {
        $parentMock    = $this->createMock(Page::class);
        $childMock     = $this->createMock(Page::class);
        $children      = [2 => $childMock];
        $transChildren = $this->createMock(Collection::class);
        $ids           = [1, 3];
        $parameters    = ['parent' => $parentMock, 'children' => $children];
        $event         = new DetermineWinnerEvent($parameters);
        $startDate     = new \DateTime();
        $translation   = 'bounces';

        $bounces = [
            1 => [
                'totalHits' => 20,
                'bounces'   => 5,
                'rate'      => 25,
                'title'     => 'Page 1.1',
                ],
            2 => [
                'totalHits' => 10,
                'bounces'   => 1,
                'rate'      => 10,
                'title'     => 'Page 1.2',
                ],
            3 => [
                'totalHits' => 30,
                'bounces'   => 15,
                'rate'      => 50,
                'title'     => 'Page 2.1',
            ],
            4 => [
                'totalHits' => 10,
                'bounces'   => 5,
                'rate'      => 50,
                'title'     => 'Page 2.2',
            ],
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn($translation);

        $parentMock->expects($this->any())
            ->method('hasTranslations')
            ->willReturn(true);

        $childMock->expects($this->any())
            ->method('hasTranslations')
            ->willReturn(true);

        $transChildren->method('getKeys')
            ->willReturnOnConsecutiveCalls([2], [4]);

        $parentMock->expects($this->any())
            ->method('getTranslationChildren')
            ->willReturn($transChildren);

        $childMock->expects($this->any())
            ->method('getTranslationChildren')
            ->willReturn($transChildren);

        $parentMock->expects($this->once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $childMock->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $this->hitRepository->expects($this->once())
            ->method('getBounces')
            ->with($ids, $startDate)
            ->willReturn($bounces);

        $this->subscriber->onDetermineBounceRateWinner($event);

        $expectedData = [20, 50];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [3]);
        $this->assertEquals($abTestResults['support']['data'][$translation], $expectedData);
    }

    public function testOnDetermineDwellTimeWinner()
    {
        $parentMock  = $this->createMock(Page::class);
        $ids         = [1, 2];
        $parameters  = ['parent' => $parentMock];
        $event       = new DetermineWinnerEvent($parameters);
        $startDate   = new \DateTime();
        $translation = 'dewlltime';

        $counts = [
            1 => [
                'sum'     => 1000,
                'min'     => 5,
                'max'     => 200,
                'average' => 50,
                'count'   => 10,
                'title'   => 'title',
            ],
            2 => [
                'sum'     => 2000,
                'min'     => 10,
                'max'     => 300,
                'average' => 70,
                'count'   => 50,
                'title'   => 'title',
            ],
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn($translation);

        $parentMock->expects($this->once())
            ->method('getRelatedEntityIds')
            ->willReturn($ids);

        $parentMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $parentMock->expects($this->once())
            ->method('getVariantStartDate')
            ->willReturn($startDate);

        $this->hitRepository->expects($this->once())
            ->method('getDwellTimesForPages')
            ->with($ids, ['fromDate' => $startDate])
            ->willReturn($counts);

        $this->subscriber->onDetermineDwellTimeWinner($event);

        $expectedData = [50, 70];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [2]);
        $this->assertEquals($abTestResults['support']['data'][$translation], $expectedData);
    }
}
