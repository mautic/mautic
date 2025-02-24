<?php

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\PageEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class PageSubscriberTest extends TestCase
{
    public function testGetTokensWhenCalledReturnsValidTokens(): void
    {
        $translator       = $this->createMock(Translator::class);
        $pageBuilderEvent = new PageBuilderEvent($translator);
        $pageBuilderEvent->addToken('{token_test}', 'TOKEN VALUE');
        $tokens = $pageBuilderEvent->getTokens();
        $this->assertArrayHasKey('{token_test}', $tokens);
        $this->assertEquals($tokens['{token_test}'], 'TOKEN VALUE');
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
            $this->createMock(Page::class)
        );
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);

        $this->assertEquals(
            $event->getContent(),
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
EOF
        );
    }

    /**
     * Get page subscriber with mocked dependencies.
     */
    protected function getPageSubscriber(): PageSubscriber
    {
        /** @var Packages&MockObject $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        $assetsHelperMock     = new AssetsHelper($packagesMock);
        $ipLookupHelperMock   = $this->createMock(IpLookupHelper::class);
        $auditLogModelMock    = $this->createMock(AuditLogModel::class);
        $hitRepository        = $this->createMock(HitRepository::class);
        $contactRepository    = $this->createMock(LeadRepository::class);
        $hitMock              = $this->createMock(Hit::class);
        $leadMock             = $this->createMock(Lead::class);
        $languageHelper       = $this->createMock(LanguageHelper::class);

        $assetsHelperMock->addScriptDeclaration("const foo='bar';", 'onPageDisplay_bodyOpen');

        $hitRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($hitMock));

        $contactRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($leadMock));

        return new PageSubscriber(
            $assetsHelperMock,
            $ipLookupHelperMock,
            $auditLogModelMock,
            $languageHelper
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
