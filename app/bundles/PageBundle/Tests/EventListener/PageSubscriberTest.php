<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\InstallBundle\Install\InstallService;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\Model\PageDraftModel;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

final class PageSubscriberTest extends TestCase
{
    public function testGetTokensWhenCalledReturnsValidTokens(): void
    {
        $translator       = $this->createStub(Translator::class);
        $pageBuilderEvent = new PageBuilderEvent($translator);
        $pageBuilderEvent->addToken('{token_test}', 'TOKEN VALUE');
        $tokens = $pageBuilderEvent->getTokens();
        $this->assertArrayHasKey('{token_test}', $tokens);
        $this->assertEquals('TOKEN VALUE', $tokens['{token_test}']);
    }

    public function testOnPageDisplayBodyTagRegex(): void
    {
        $dummyPageContent = <<<EOF
<html>
    <head>
    </head>
    <body class="mt-6 md:max-w-2xl p-[5px]"  onclick="myFunction()" data-help-text="téxt with nön äscii charactêrs">
    </body>
</html>
EOF;
        $event = new PageDisplayEvent(
            $dummyPageContent,
            $this->createStub(Page::class)
        );
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $dispatcher->dispatch($event);

        $this->assertSame(
            <<<EOF
<html>
    <head>
    </head>
    <body class="mt-6 md:max-w-2xl p-[5px]"  onclick="myFunction()" data-help-text="téxt with nön äscii charactêrs">
<script data-source="mautic">
const foo='bar';
</script>

    </body>
</html>
EOF,
            $event->getContent()
        );
    }

    /**
     * Get page subscriber with mocked dependencies.
     */
    protected function getPageSubscriber(): PageSubscriber
    {
        $pathsHelper        = $this->createStub(PathsHelper::class);
        $assetsHelperMock   = new AssetsHelper(
            $this->createStub(Packages::class),
            $pathsHelper,
            new AssetGenerationHelper(
                $this->createStub(BundleHelper::class),
                $pathsHelper,
                $this->createStub(CoreParametersHelper::class),
                $this->createStub(AppVersion::class),
            ),
            $this->createStub(BuilderIntegrationsHelper::class),
            $this->createStub(InstallService::class),
            '',
        );

        $assetsHelperMock->addScriptDeclaration("const foo='bar';", 'onPageDisplay_bodyOpen');

        return new PageSubscriber(
            $assetsHelperMock,
            $this->createStub(IpLookupHelper::class),
            $this->createStub(AuditLogModel::class),
            $this->createStub(LanguageHelper::class),
            $this->createStub(PageModel::class),
            $this->createStub(PageDraftModel::class),
        );
    }

    /**
     * Get non empty payload, having a Request and non-null entity IDs.
     *
     * @return array<string, bool|int|MockObject>
     */
    protected function getNonEmptyPayload(): array
    {
        $requestMock = $this->createMock(Request::class);

        return [
            'request' => $requestMock,
            'isNew'   => true,
            'hitId'   => 123,
            'pageId'  => 456,
            'leadId'  => 789,
        ];
    }

    /**
     * Get empty payload with all null entity IDs.
     *
     * @return array<string, null>
     */
    protected function getEmptyPayload(): array
    {
        return array_fill_keys(['request', 'isNew', 'hitId', 'pageId', 'leadId'], null);
    }
}
