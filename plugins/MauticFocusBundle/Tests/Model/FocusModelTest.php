<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
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
use MauticPlugin\MauticFocusBundle\EventListener\FocusSubscriber;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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

    public function testDisplayGenerationReplacesPageAndAssetLinksWithoutTracking(): void
    {
        $trackableModel = $this->createMock(TrackableModel::class);
        $trackableModel->expects($this->never())->method('parseContentForTrackables');
        $trackableModel->expects($this->never())->method('generateTrackableUrl');
        $pageTokenHelper = $this->createMock(PageTokenHelper::class);
        $pageTokenHelper->expects($this->once())
            ->method('findPageTokens')
            ->willReturnCallback(function (string $content, array $clickthrough = []): array {
                $this->assertSame([], $clickthrough, 'page links are plain URLs before consent');

                return ['{pagelink=1}' => 'https://example.com/page'];
            });
        $assetTokenHelper = $this->createMock(AssetTokenHelper::class);
        $assetTokenHelper->expects($this->once())
            ->method('findAssetTokens')
            ->willReturnCallback(function (string $content, array $clickthrough = []): array {
                $this->assertSame([], $clickthrough, 'asset links are plain URLs before consent');

                return ['{assetlink=2}' => 'https://example.com/asset'];
            });
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new FocusSubscriber(
            $this->createStub(RouterInterface::class),
            $this->createStub(IpLookupHelper::class),
            $this->createStub(AuditLogModel::class),
            $trackableModel,
            $pageTokenHelper,
            $assetTokenHelper,
            $this->createStub(FocusModel::class),
            $this->createStub(RequestStack::class),
        ));
        $focusModel = $this->createDisplayFocusModel(
            $dispatcher,
            '<a href="{pagelink=1}">Page</a><a href="{assetlink=2}">Asset</a>',
            $trackableModel,
        );

        $content = $focusModel->generateJavascript($this->createDisplayFocus(), false, [FocusJsScope::RUNTIME, FocusJsScope::DISPLAY]);

        $this->assertStringContainsString('example.com\\/page', $content);
        $this->assertStringContainsString('example.com\\/asset', $content);
        $this->assertStringNotContainsString('{pagelink=1}', $content);
        $this->assertStringNotContainsString('{assetlink=2}', $content);
    }

    public function testDisplayGenerationDispatchesTokenReplacementWithoutAContact(): void
    {
        $dispatcher = new EventDispatcher();
        $seen       = null;
        $dispatcher->addListener(FocusEvents::TOKEN_REPLACEMENT, static function (TokenReplacementEvent $event) use (&$seen): void {
            $seen = ['lead' => $event->getLead(), 'content' => $event->getContent(), 'clickthrough' => $event->getClickthrough()];
            $event->setContent(str_replace('{plugin_token}', 'plugin value', (string) $event->getContent()));
        });
        $focusModel = $this->createDisplayFocusModel($dispatcher, '<div>{plugin_token} {contactfield=firstname|there}</div>');

        $content = $focusModel->generateJavascript($this->createDisplayFocus(), false, [FocusJsScope::RUNTIME, FocusJsScope::DISPLAY]);

        // Plugins get the event without a contact, and contact tokens are already replaced by their defaults
        $this->assertNotNull($seen);
        $this->assertNull($seen['lead']);
        $this->assertArrayHasKey('focus_id', $seen['clickthrough']);
        $this->assertStringNotContainsString('{contactfield', (string) $seen['content']);
        $this->assertStringContainsString('there', (string) $seen['content']);
        // The content is escaped for JavaScript, so the space is written as a unicode escape
        $this->assertStringContainsString('plugin\\u0020value', $content);
        $this->assertStringNotContainsString('plugin_token', $content);
    }

    private function createDisplayFocusModel(EventDispatcherInterface $dispatcher, string $focusContent, ?TrackableModel $trackableModel = null): FocusModel
    {
        $formModel = $this->createStub(FormModel::class);
        $formModel->method('getPages')->willReturn([[], false]);
        $twig = new Environment(new ArrayLoader([
            '@MauticFocus/Builder/generate.js.twig' => 'var content = "{focus_content}";',
            '@MauticFocus/Builder/content.html.twig'  => $focusContent,
        ]));

        return new FocusModel(
            $formModel,
            $trackableModel ?? $this->createStub(TrackableModel::class),
            $twig,
            $this->createStub(FieldModel::class),
            $this->createStub(ContactTracker::class),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(CorePermissions::class),
            $dispatcher,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(FocusRepository::class),
            $this->createStub(StatRepository::class),
        );
    }

    private function createDisplayFocus(): Focus
    {
        $focus = new Focus();
        $focus->setName('Display links');
        $focus->setType('notice');
        $focus->setStyle('modal');

        return $focus;
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
