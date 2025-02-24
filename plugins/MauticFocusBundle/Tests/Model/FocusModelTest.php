<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class FocusModelTest extends TestCase
{
    /**
     * @var ContactTracker|MockObject
     */
    private MockObject $contactTracker;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var FormModel|MockObject
     */
    private MockObject $formModel;

    /**
     * @var FieldModel|MockObject
     */
    private MockObject $leadFieldModel;

    /**
     * @var Environment|mixed|MockObject
     */
    private MockObject $twig;

    /**
     * @var TrackableModel|mixed|MockObject
     */
    private MockObject $trackableModel;

    protected function setUp(): void
    {
        $this->formModel      = $this->createMock(FormModel::class);
        $this->trackableModel = $this->createMock(TrackableModel::class);
        $this->twig           = $this->createMock(Environment::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $this->leadFieldModel = $this->createMock(FieldModel::class);
        $this->contactTracker = $this->createMock(ContactTracker::class);
        parent::setUp();
    }

    /**
     * @dataProvider focusTypeProvider
     */
    public function testGetContentWithForm(string $type, InvokedCount $count): void
    {
        $this->formModel->expects(self::once())->method('getPages')->willReturn(['', '']);

        $this->formModel->expects($count)->method('getEntity');

        $focusModel = new FocusModel(
            $this->formModel,
            $this->trackableModel,
            $this->twig,
            $this->leadFieldModel,
            $this->contactTracker,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $this->dispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );
        $focus = [
            'form' => 'xxx',
            'type' => $type,
        ];

        $focusModel->getContent($focus);
    }

    public function focusTypeProvider(): \Generator
    {
        yield ['form', self::once()];
        yield ['notice', self::never()];
    }
}
