<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\EventListener\LeadSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\VideoModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadSubscriberTest extends TestCase
{
    /**
     * @var MockObject&PrimaryCompanyHelper
     */
    private MockObject $primaryCompanyHelper;

    /**
     * @var MockObject&HitRepository
     */
    private MockObject $hitRepository;

    private LeadSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hitRepository        = $this->createMock(HitRepository::class);
        $this->primaryCompanyHelper = $this->createMock(PrimaryCompanyHelper::class);

        $pageModel = $this->createMock(PageModel::class);
        $pageModel->method('getHitRepository')->willReturn($this->hitRepository);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $this->subscriber = new LeadSubscriber(
            $pageModel,
            $this->createStub(VideoModel::class),
            $translator,
            $this->createStub(RouterInterface::class),
            $this->createStub(ModelFactory::class),
            $this->primaryCompanyHelper
        );
    }

    public function testTrackedLinkTokensAreResolvedForTheContact(): void
    {
        $this->hitRepository->method('getLeadHits')->willReturn([
            'results' => [
                [
                    'hitId'   => 1,
                    'lead_id' => 5,
                    'url'     => 'mailto:{contactfield=account_manager_email}',
                    'dateHit' => new \DateTime(),
                ],
            ],
            'total' => 1,
        ]);

        $this->primaryCompanyHelper->method('getProfileFieldsWithPrimaryCompany')
            ->willReturn(['account_manager_email' => 'manager@example.com']);

        $events = $this->generateTimeline();

        $this->assertSame('mailto:manager@example.com', $events[0]['eventLabel']['href']);
        $this->assertSame('mailto:manager@example.com', $events[0]['eventLabel']['label']);
    }

    public function testUrlWithoutTokensIsLeftAloneAndContactIsNotLoaded(): void
    {
        $this->hitRepository->method('getLeadHits')->willReturn([
            'results' => [
                [
                    'hitId'   => 2,
                    'lead_id' => 5,
                    'url'     => 'https://example.com/pricing',
                    'dateHit' => new \DateTime(),
                ],
            ],
            'total' => 1,
        ]);

        $this->primaryCompanyHelper->expects($this->never())
            ->method('getProfileFieldsWithPrimaryCompany');

        $events = $this->generateTimeline();

        $this->assertSame('https://example.com/pricing', $events[0]['eventLabel']['href']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateTimeline(): array
    {
        $lead = new Lead();
        $lead->setId(5);

        $event = new LeadTimelineEvent($lead);
        $this->subscriber->onTimelineGenerate($event);

        return array_values($event->getEvents());
    }
}
