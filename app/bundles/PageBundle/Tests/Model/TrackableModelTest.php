<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(TrackableModel::class)]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class TrackableModelTest extends TestCase
{
    #[TestDox('Test that content is detected as HTML')]
    public function testHtmlIsDetectedInContent(): void
    {
        $mockRedirectModel       = $this->createStub(RedirectModel::class);
        $mockLeadFieldRepository = $this->createStub(LeadFieldRepository::class);

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(CorePermissions::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(UrlGeneratorInterface::class),
                $this->createStub(Translator::class),
                $this->createStub(UserHelper::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(CoreParametersHelper::class),
            ])
            ->onlyMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromHtml'])
            ->getMock();

        $mockModel->autowireTrackableModel($mockRedirectModel, $mockLeadFieldRepository, $this->createStub(TrackableRepository::class));

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromHtml')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        [$content, $trackables] = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'html'),
            [],
            'email',
            1
        );
    }

    #[TestDox('Test that content is detected as plain text')]
    public function testPlainTextIsDetectedInContent(): void
    {
        $mockRedirectModel       = $this->createStub(RedirectModel::class);
        $mockLeadFieldRepository = $this->createStub(LeadFieldRepository::class);

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(CorePermissions::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(UrlGeneratorInterface::class),
                $this->createStub(Translator::class),
                $this->createStub(UserHelper::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(CoreParametersHelper::class),
            ])
            ->onlyMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromText'])
            ->getMock();

        $mockModel->autowireTrackableModel($mockRedirectModel, $mockLeadFieldRepository, $this->createStub(TrackableRepository::class));

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromText')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        [$content, $trackables] = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'text'),
            [],
            'email',
            1
        );
    }

    #[DataProvider('trackMapProvider')]
    #[TestDox('Test that a standard link with a standard query is parsed correctly')]
    public function testStandardLinkWithStandardQuery(?bool $useMap): void
    {
        $url   = 'https://foo-bar.com?foo=bar&amp;one=two&three=four&amp;five=six';
        $model = $this->getModel();

        if (null !== $useMap) {
            $emailContent = $this->generateContent($url, 'html', $useMap);
        } else {
            $emailContent = $this->generateContent($url, 'html', true)
                .$this->generateContent($url, 'html', false);
        }

        [$content, $trackables] = $model->parseContentForTrackables(
            $emailContent,
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that exactly one trackable found
        $this->assertCount(1, $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals(str_replace('&amp;', '&', $url), $redirect->getUrl());
    }

    #[DataProvider('trackMapProvider')]
    #[TestDox('Test that a standard link without a query parses correctly')]
    public function testStandardLinkWithoutQuery(?bool $useMap): void
    {
        $url   = 'https://foo-bar.com';
        $model = $this->getModel();

        if (null !== $useMap) {
            $emailContent = $this->generateContent($url, 'html', $useMap);
        } else {
            $emailContent = $this->generateContent($url, 'html', true)
                .$this->generateContent($url, 'html', false);
        }

        [$content, $trackables] = $model->parseContentForTrackables(
            $emailContent,
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that exactly one trackable found
        $this->assertCount(1, $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    #[DataProvider('trackMapProvider')]
    #[TestDox('Test that a standard link with a tokenized query parses correctly')]
    public function testStandardLinkWithTokenizedQuery(?bool $useMap): void
    {
        $url   = 'https://foo-bar.com?foo={contactfield=bar}&bar=foo';
        $model = $this->getModel();

        if (null !== $useMap) {
            $emailContent = $this->generateContent($url, 'html', $useMap);
        } else {
            $emailContent = $this->generateContent($url, 'html', true)
                .$this->generateContent($url, 'html', false);
        }

        [$content, $trackables] = $model->parseContentForTrackables(
            $emailContent,
            [
                '{contactfield=bar}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert that exactly one trackable found
        $this->assertCount(1, $trackables);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    #[TestDox('Test that a token used in place of a URL is parsed properly')]
    public function testTokenizedDomain(): void
    {
        $url   = 'http://{contactfield=foo}.org';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => 'mautic',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    public function testTokenizedHostWithScheme(): void
    {
        $url   = '{contactfield=foo}';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => 'https://mautic.org',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    #[TestDox('Test that a token used in place of a URL is parsed')]
    public function testTokenizedHostWithQuery(): void
    {
        $url   = 'http://{contactfield=foo}.com?foo=bar';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    public function testTokenizedHostWithTokenizedQuery(): void
    {
        $url   = 'http://{contactfield=foo}.com?foo={contactfield=bar}';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => '',
                '{contactfield=bar}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    public function testOwnerFieldTokenizedHostWithoutTokenValue(): void
    {
        $url   = 'https://{ownerfield=firstname}.com';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    #[TestDox('Test that tokens that are supposed to be ignored are')]
    public function testIgnoredTokensAreNotConverted(): void
    {
        $url   = 'https://{unsubscribe_url}';
        $model = $this->getModel(['{unsubscribe_url}']);

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
        $this->assertStringNotContainsString($url, (string) $content, 'https:// should have been stripped from the token URL');
    }

    #[TestDox('Test that tokens that are supposed to be ignored are')]
    public function testUnsupportedTokensAreNotConverted(): void
    {
        $url   = '{random_token}';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'text'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
    }

    public function testTokenWithDefaultValueInPlaintextWillCountAsOne(): void
    {
        $url          = '{contactfield=website|https://mautic.org}';
        $model        = $this->getModel();
        $inputContent = $this->generateContent($url, 'text');

        [$content, $trackables] = $model->parseContentForTrackables(
            $inputContent,
            [
                '{contactfield=website}' => 'https://mautic.org/about-us',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $trackableKey = '{trackable='.$match[1].'}';
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);

        $this->assertCount(1, $trackables);
        $this->assertEquals('{contactfield=website|https://mautic.org}', $trackables[$trackableKey]->getRedirect()->getUrl());
    }

    #[TestDox('Test that a URL injected into the do not track list is not converted')]
    public function testIgnoredUrlDoesNotCrash(): void
    {
        $url   = 'https://domain.com';
        $model = $this->getModel([$url]);

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $this->assertStringContainsString($url, (string) $content, $content);
    }

    public function testMalformedUrlDoesNotCrashTrackingParsing(): void
    {
        $url   = '://example.com';
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $this->assertEmpty($trackables);
        $this->assertStringContainsString($url, (string) $content);
    }

    #[DataProvider('trackMapProvider')]
    #[TestDox('Test that a token used in place of a URL is not parsed')]
    public function testTokenAsHostIsConvertedToTrackableToken(?bool $useMap): void
    {
        $url   = 'http://{pagelink=1}';
        $model = $this->getModel();

        if (null !== $useMap) {
            $emailContent = $this->generateContent($url, 'html', $useMap);
        } else {
            $emailContent = $this->generateContent($url, 'html', true)
                .$this->generateContent($url, 'html', false);
        }

        [$content, $trackables] = $model->parseContentForTrackables(
            $emailContent,
            [
                '{pagelink=1}' => 'http://foo-bar.com',
            ],
            'email',
            1
        );
        $token = array_key_first($trackables);
        $this->assertNotEmpty($trackables, $content);
        $this->assertStringContainsString($token, (string) $content);

        // Assert that exactly one trackable found
        $this->assertCount(1, $trackables);
    }

    #[DataProvider('trackMapProvider')]
    #[TestDox('Test that a URLs with same base or correctly replaced')]
    public function testUrlsWithSameBaseAreReplacedCorrectly(?bool $useMap): void
    {
        $urls = [
            'https://foo-bar.com',
            'https://foo-bar.com?foo=bar',
            'https://FOO-bar.com/bar',
        ];

        $model = $this->getModel();

        if (null !== $useMap) {
            $emailContent = $this->generateContent($urls, 'html', $useMap);
        } else {
            $emailContent = $this->generateContent($urls, 'html', true)
                .$this->generateContent($urls, 'html', false);
        }

        [$content, $trackables] = $model->parseContentForTrackables(
            $emailContent,
            [],
            'email',
            1
        );

        // Assert that both trackables found
        $this->assertCount(3, $trackables);

        foreach ($trackables as $redirectId => $trackable) {
            // If the shared base was correctly parsed, all generated tokens will be in the content
            $this->assertStringContainsString((string) $redirectId, (string) $content, $content);
        }
    }

    #[TestDox('Test that css images are not converted if there are no links')]
    public function testCssUrlsAreNotConvertedIfThereAreNoLinks(): void
    {
        $model = $this->getModel();

        [$content, $trackables] = $model->parseContentForTrackables(
            '<style> .mf-modal { background-image: url(\'https://www.mautic.org/wp-content/uploads/2014/08/iTunesArtwork.png\'); } </style>',
            [],
            'email',
            1
        );

        $this->assertEmpty($trackables);
    }

    #[TestDox('Tests that URLs in the plaintext does not contaminate HTML')]
    public function testPlainTextDoesNotContaminateHtml(): void
    {
        $model = $this->getModel();

        $html = <<<TEXT
Hi {contactfield=firstname},
<br />
Come to our office in {contactfield=city}! 
<br />
John Smith<br />
VP of Sales<br />
https://plaintexttest.io
TEXT;

        $plainText = strip_tags($html);

        $combined                   = [$html, $plainText];
        [$content, $trackables]     = $model->parseContentForTrackables(
            $combined,
            [],
            'email',
            1
        );

        $this->assertCount(1, $trackables);

        // No links so no trackables
        $this->assertSame($html, $content[0]);
        $token = array_key_first($trackables);
        $this->assertNotNull($token);

        $this->assertSame(str_replace('https://plaintexttest.io', $token, $plainText), $content[1]);
    }

    #[TestDox('Tests that URL based contact fields are found in plain text')]
    public function testPlainTextFindsUrlContactFields(): void
    {
        $model = $this->getModel([], ['website']);

        $html = <<<TEXT
Hi {contactfield=firstname},
<br />
Come to our office in {contactfield=city}! 
<br />
John Smith<br />
VP of Sales<br />
{contactfield=website}
TEXT;

        $plainText = strip_tags($html);

        $combined                   = [$html, $plainText];
        [$content, $trackables]     = $model->parseContentForTrackables(
            $combined,
            [],
            'email',
            1
        );

        $this->assertCount(1, $trackables);

        // No links so no trackables
        $this->assertSame($html, $content[0]);
        $token = array_key_first($trackables);
        $this->assertNotNull($token);

        $this->assertSame(str_replace('{contactfield=website}', $token, $plainText), $content[1]);
    }

    /**
     * @param array<int, string>        $doNotTrack
     * @param array<string|int, string> $urlFieldsForPlaintext
     *
     * @return TrackableModel&MockObject
     */
    protected function getModel(array $doNotTrack = [], array $urlFieldsForPlaintext = []): MockObject
    {
        // Add default DoNotTrack
        $doNotTrack = array_merge(
            $doNotTrack,
            [
                '{webview_url}',
                '{unsubscribe_url}',
                '{trackable=(.*?)}',
            ]
        );

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(CorePermissions::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(UrlGeneratorInterface::class),
                $this->createStub(Translator::class),
                $this->createStub(UserHelper::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(CoreParametersHelper::class),
            ])
            ->onlyMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'getContactFieldUrlTokens'])
            ->getMock();

        $mockModel->autowireTrackableModel($this->createMock(RedirectModel::class), $this->createMock(LeadFieldRepository::class), $this->createStub(TrackableRepository::class));

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn($doNotTrack);

        $mockModel
            ->method('getEntitiesFromUrls')
            ->willReturnCallback(
                function ($trackableUrls, $channel, $channelId): array {
                    $entities = [];
                    foreach ($trackableUrls as $url) {
                        $entities[$url] = $this->getTrackableEntity($url);
                    }

                    return $entities;
                }
            );

        $mockModel
            ->method('getContactFieldUrlTokens')
            ->willReturn($urlFieldsForPlaintext);

        return $mockModel;
    }

    protected function getTrackableEntity(string $url): Trackable
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $trackable = new Trackable();
        $trackable->setChannel('email')
            ->setChannelId(1)
            ->setRedirect($redirect)
            ->setHits(random_int(1, 10))
            ->setUniqueHits(random_int(1, 10));

        return $trackable;
    }

    /**
     * @param array<int, string>|string $urls
     */
    protected function generateContent($urls, string $type, bool $useMap = false): string
    {
        $content = '';
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        foreach ($urls as $url) {
            if ('html' === $type) {
                if ($useMap) {
                    $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 <map><area href="{$url}" alt="alt" /></map> 321ABC
CONTENT;
                } else {
                    $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 <a href="{$url}">{$url}</a> 321ABC
CONTENT;
                }
            } else {
                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 {$url} 321ABC
CONTENT;
            }
        }

        return $content;
    }

    /**
     * @return \Iterator<(int|string), array<(bool|null)>> Use null to include both <a> and <map> tags
     */
    public static function trackMapProvider(): \Iterator
    {
        yield [true];
        yield [false];
        yield [null];
    }
}
