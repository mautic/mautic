<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\EventListener\DetermineWinnerSubscriber;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class DetermineWinnerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&SubmissionRepository
     */
    private MockObject $submissionRepository;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    private DetermineWinnerSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->submissionRepository = $this->createMock(SubmissionRepository::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->subscriber           = new DetermineWinnerSubscriber($this->submissionRepository, $this->translator);
    }

    public function testOnDetermineSubmissionWinner(): void
    {
        $parent           = new Page();
        $child            = new Page();
        $children         = [2 => $child];
        $parameters       = ['parent' => $parent, 'children' => $children];
        $event            = new DetermineWinnerEvent($parameters);
        $startDate        = new \DateTime();
        $transSubmissions = 'submissions';
        $transHits        = 'hits';
        $counts           = [
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
            ->willReturnOnConsecutiveCalls($transSubmissions, $transHits);

        $parent->setIsPublished(true);
        $child->setIsPublished(true);
        new \ReflectionProperty($parent, 'id')->setValue($parent, 1);
        new \ReflectionProperty($child, 'id')->setValue($child, 2);
        $parent->setVariantStartDate($startDate);

        $this->submissionRepository->expects($this->once())
            ->method('getSubmissionCountsByPage')
            ->with([1, 2], $startDate)
            ->willReturn($counts);

        $this->subscriber->onDetermineSubmissionWinner($event);

        $expectedData = [
            $transSubmissions => [$counts[1]['count'], $counts[2]['count']],
            $transHits        => [$counts[1]['total'], $counts[2]['total']],
        ];

        $abTestResults = $event->getAbTestResults();

        $this->assertEquals($abTestResults['winners'], [1]);
        $this->assertEquals($abTestResults['support']['data'], $expectedData);
    }
}
