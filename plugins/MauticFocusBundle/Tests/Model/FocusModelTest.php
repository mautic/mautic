<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\DelegatingEngine;

class FocusModelTest extends TestCase
{
    /**
     * @var ContactTracker|MockObject
     */
    private $contactTracker;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FormModel|MockObject
     */
    private $formModel;

    /**
     * @var FieldModel|MockObject
     */
    private $leadFieldModel;

    /**
     * @var TemplatingHelper|mixed|MockObject
     */
    private $templating;

    /**
     * @var TrackableModel|mixed|MockObject
     */
    private $trackableModel;

    protected function setUp(): void
    {
        $this->formModel      = $this->createMock(FormModel::class);
        $this->trackableModel = $this->createMock(TrackableModel::class);
        $this->templating     = $this->createMock(TemplatingHelper::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->leadFieldModel = $this->createMock(FieldModel::class);
        $this->contactTracker = $this->createMock(ContactTracker::class);
        parent::setUp();
    }

    /**
     * @dataProvider focusTypeProvider
     */
    public function testGetContentWithForm(string $type, InvokedCount $count)
    {
        $templating = $this->createMock(DelegatingEngine::class);
        $this->templating->expects(self::once())->method('getTemplating')->willReturn($templating);

        $this->formModel->expects(self::once())->method('getPages')->willReturn(['', '']);

        $this->formModel->expects($count)->method('getEntity');

        $focusModel = new FocusModel(
            $this->formModel,
            $this->trackableModel,
            $this->templating,
            $this->dispatcher,
            $this->leadFieldModel,
            $this->contactTracker);
        $focus = [
            'form' => 'xxx',
            'type' => $type,
        ];

        $focusModel->getContent($focus);
    }

    public function focusTypeProvider(): iterable
    {
        yield ['form', self::once()];
        yield ['notice', self::never()];
    }
}
