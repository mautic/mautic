<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Entity\StatRepository;
use MauticPlugin\MauticFocusBundle\Enum\FocusJsScope;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class FocusModelTest extends TestCase
{
    /**
     * @var MockObject&FormModel
     */
    private MockObject $formModel;

    protected function setUp(): void
    {
        $this->formModel      = $this->createMock(FormModel::class);
        parent::setUp();
    }

    #[DataProvider('focusTypeProvider')]
    public function testGetContentWithForm(string $type, InvokedCount $count): void
    {
        $this->formModel->expects($this->once())->method('getPages')->willReturn(['', '']);

        $this->formModel->expects($count)->method('getEntity');

        $focusModel = new FocusModel(
            $this->formModel,
            $this->createStub(TrackableModel::class),
            $this->createStub(Environment::class),
            $this->createStub(FieldModel::class),
            $this->createStub(ContactTracker::class),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(FocusRepository::class), // $focusRepository
            $this->createStub(StatRepository::class), // $statRepository
            $this->createStub(PageTokenHelper::class),
            $this->createStub(AssetTokenHelper::class),
        );
        $focus = [
            'form' => 'xxx',
            'type' => $type,
        ];

        $focusModel->getContent($focus);
    }

    public static function focusTypeProvider(): \Generator
    {
        yield ['form', new InvokedCountMatcher(1)];
        yield ['notice', new InvokedCountMatcher(0)];
    }

    #[DataProvider('anonymousClickUrlProvider')]
    public function testDisplayGenerationDoesNotResolveTrackedContact(string $linkUrl, string $expectedClickUrl): void
    {
        $contactTracker = $this->createMock(ContactTracker::class);
        $contactTracker->expects($this->never())->method('getContact');
        $trackableModel = $this->createMock(TrackableModel::class);
        $trackableModel->expects($this->never())->method('getTrackableByUrl');
        $trackableModel->expects($this->never())->method('generateTrackableUrl');
        $formModel = $this->createStub(FormModel::class);
        $formModel->method('getPages')->willReturn([[], false]);
        $twig = new Environment(new ArrayLoader([
            '@MauticFocus/Builder/generate.js.twig' => 'var clickUrl = "{{ clickUrl }}"; var content = "{focus_content}";',
            '@MauticFocus/Builder/content.html.twig'  => '<div>Anonymous {contactfield=date|datetime}</div>',
        ]));
        $focusModel = new FocusModel(
            $formModel,
            $trackableModel,
            $twig,
            $this->createStub(FieldModel::class),
            $contactTracker,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(FocusRepository::class),
            $this->createStub(StatRepository::class),
            $this->createStub(PageTokenHelper::class),
            $this->createStub(AssetTokenHelper::class),
        );
        $focus = new Focus();
        $focus->setName('Anonymous display');
        $focus->setType('link');
        $focus->setStyle('modal');
        $focus->setProperties([
            'content' => [
                'link_url' => $linkUrl,
            ],
        ]);

        $content = $focusModel->generateJavascript($focus, false, [FocusJsScope::RUNTIME, FocusJsScope::DISPLAY]);

        $this->assertStringContainsString($expectedClickUrl, $content);
        $this->assertStringNotContainsString('Anonymous datetime', $content);
    }

    public function testDisplayGenerationReplacesPageAndAssetLinks(): void
    {
        $trackableModel = $this->createMock(TrackableModel::class);
        $trackableModel->expects($this->never())->method('generateTrackableUrl');
        $formModel = $this->createStub(FormModel::class);
        $formModel->method('getPages')->willReturn([[], false]);
        $twig = new Environment(new ArrayLoader([
            '@MauticFocus/Builder/generate.js.twig' => 'var content = "{focus_content}";',
            '@MauticFocus/Builder/content.html.twig'  => '<a href="{pagelink=1}">Page</a><a href="{assetlink=2}">Asset</a>',
        ]));
        $pageTokenHelper = $this->createStub(PageTokenHelper::class);
        $pageTokenHelper->method('findPageTokens')->willReturn(['{pagelink=1}' => 'https://example.com/page']);
        $assetTokenHelper = $this->createStub(AssetTokenHelper::class);
        $assetTokenHelper->method('findAssetTokens')->willReturn(['{assetlink=2}' => 'https://example.com/asset']);
        $focusModel = new FocusModel(
            $formModel,
            $trackableModel,
            $twig,
            $this->createStub(FieldModel::class),
            $this->createStub(ContactTracker::class),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(FocusRepository::class),
            $this->createStub(StatRepository::class),
            $pageTokenHelper,
            $assetTokenHelper,
        );
        $focus = new Focus();
        $focus->setName('Display links');
        $focus->setType('notice');
        $focus->setStyle('modal');

        $content = $focusModel->generateJavascript($focus, false, [FocusJsScope::RUNTIME, FocusJsScope::DISPLAY]);

        $this->assertStringContainsString('example.com\\/page', $content);
        $this->assertStringContainsString('example.com\\/asset', $content);
        $this->assertStringNotContainsString('{pagelink=1}', $content);
        $this->assertStringNotContainsString('{assetlink=2}', $content);
    }

    /**
     * @return \Generator<string[]>
     */
    public static function anonymousClickUrlProvider(): \Generator
    {
        yield 'token default' => ['https://example.com/{contactfield=firstname|visitor}', 'https://example.com/visitor'];
        yield 'no token default' => ['https://{contactfield=firstname}/tour', '#'];
        yield 'empty token default' => ['https://{contactfield=firstname||visitor}/tour', '#'];
        yield 'URL encoding modifier' => ['https://example.com/{contactfield=website|true}', '#'];
        yield 'date and time modifier' => ['https://example.com/{contactfield=date|datetime}', '#'];
        yield 'date modifier' => ['https://example.com/{contactfield=date|date}', '#'];
        yield 'time modifier' => ['https://example.com/{contactfield=date|time}', '#'];
        yield 'label modifier' => ['https://example.com/{contactfield=select|label}', '#'];
    }
}
