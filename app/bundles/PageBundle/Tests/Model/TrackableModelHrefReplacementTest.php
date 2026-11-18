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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Links in an email were sent to the wrong destination (MAUT-13834, ported from acquia/mc-cs#3169).
 *
 * TrackableModel::prepareContentWithTrackableTokens() replaces each tracked URL in the HTML with a
 * {trackable=ID} token using a regex that
 *   1. has no closing-quote anchor after the URL, so it matches any href that merely STARTS with a tracked URL
 *      and re-appends the remainder of the href after the token ("$3"), and
 *   2. searches the raw body for a literal "=", while URL extraction reads hrefs through DOMDocument (which decodes
 *      entities), so a body containing "&#61;" registers the URL but never replaces it.
 *
 * Combined, a tracked base link (https://example.com/) hijacks every longer same-host link that is not replaced by
 * its own token, e.g. {trackable=BASE}projects/foo?x&#61;1 -> every such link redirects to the base URL.
 *
 * These tests assert the CORRECT behaviour. Against the current code the tests marked "bug" are expected to FAIL;
 * the "control" tests are expected to PASS and prove the harness itself is sound.
 */
#[CoversClass(TrackableModel::class)]
final class TrackableModelHrefReplacementTest extends TestCase
{
    private const BASE = 'https://www.example.com/';

    /**
     * Same shape as the links in the affected email (one base link, two same-host links with query strings,
     * a query-string link on other hosts, and plain links with no query string).
     *
     * @var string[]
     */
    private const EMAIL_URLS = [
        'https://www.example.com/projects/Project-G1?utm_source=mautic&utm_medium=email&utm_campaign=26648&utm_content=proj-opp',
        'https://support.example.com/csp?id=kb_article_view&sysparm_article=KB0010097',
        'https://www.example.com/projects?tab=suggested',
        'https://www.example.com/',
        'http://www.facebook.com/example',
        'https://www.example.com/terms-and-conditions',
        'https://apps.example.net/us/app/example/id1492960703?ls=1',
        'https://play.example.net/store/apps/details?id=com.example.app',
    ];

    // ---------------------------------------------------------------------------------------------------------
    // Controls: these pass on the current code
    // ---------------------------------------------------------------------------------------------------------

    #[TestDox('control: with literal "=" and "&amp;" every link gets its own token')]
    public function testControlLiteralEqualsGivesEveryLinkItsOwnToken(): void
    {
        $html = $this->buildEmail(self::EMAIL_URLS, static fn (string $url): string => htmlspecialchars($url, ENT_QUOTES));

        [$content, $trackables] = $this->parse($html);

        $this->assertCount(count(self::EMAIL_URLS), $trackables);
        $this->assertEveryHrefIsExactlyOneOwnToken($content, $trackables, count(self::EMAIL_URLS));
    }

    #[TestDox('control: with literal "=" and a bare "&" every link gets its own token')]
    public function testControlLiteralEqualsAndBareAmpersand(): void
    {
        $html = $this->buildEmail(self::EMAIL_URLS, static fn (string $url): string => $url);

        [$content, $trackables] = $this->parse($html);

        $this->assertEveryHrefIsExactlyOneOwnToken($content, $trackables, count(self::EMAIL_URLS));
    }

    /**
     * This proves the registration (DOM) side is unaffected by encoding, so the failure is in the replacement side.
     */
    #[DataProvider('encodingProvider')]
    #[TestDox('control: extraction registers the SAME urls whatever entity encoding the body uses')]
    public function testControlRegistrationIsIndependentOfEncoding(string $label, callable $encode): void
    {
        [, $trackables] = $this->parse($this->buildEmail(self::EMAIL_URLS, $encode));

        $registered = array_map(static fn (Trackable $t): string => $t->getRedirect()->getUrl(), array_values($trackables));
        sort($registered);
        $expected = self::EMAIL_URLS;
        sort($expected);

        $this->assertSame($expected, $registered, $label);
    }

    // ---------------------------------------------------------------------------------------------------------
    // Bug 2: entity-encoded characters in the body are registered but never replaced
    // ---------------------------------------------------------------------------------------------------------

    #[DataProvider('encodingProvider')]
    #[TestDox('bug: every registered link is replaced with its own token when the body encodes "=" as an entity')]
    public function testEncodedQueryCharactersStillGetOwnToken(string $label, callable $encode): void
    {
        [$content, $trackables] = $this->parse($this->buildEmail(self::EMAIL_URLS, $encode));

        $this->assertEveryHrefIsExactlyOneOwnToken($content, $trackables, count(self::EMAIL_URLS), $label);
    }

    /**
     * Reproduces the production email: with "&#61;" the support/App Store/Play links stayed raw (untracked) and the
     * same-host links collapsed onto the base link's redirect.
     */
    #[TestDox('bug: a query-string link with an entity-encoded "=" is not left raw (not tracked) or sent to its host\'s base redirect')]
    public function testEntityEncodedEqualsReproducesProductionEmail(): void
    {
        $encode                 = static fn (string $url): string => str_replace('=', '&#61;', $url);
        [$content, $trackables] = $this->parse($this->buildEmail(self::EMAIL_URLS, $encode));

        $baseToken = $this->tokenFor($trackables, self::BASE);
        $hrefs     = $this->hrefs($content);

        // 1. No same-host link is collapsed onto the base token with a leaked remainder, e.g. {trackable=X}projects/...
        foreach ($hrefs as $href) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\{trackable=[^}]+\}.+/',
                $href,
                'Token followed by leaked href remainder (the prefix hijack): '.$href
            );
        }

        // 2. No registered query-string URL is left as a raw, untracked link
        foreach ($hrefs as $href) {
            $this->assertStringStartsWith('{trackable=', $href, 'Link was registered as a trackable but not replaced: '.$href);
        }

        // 3. The base token is used exactly once (only the genuine base link)
        $this->assertCount(1, array_keys($hrefs, $baseToken, true), 'Base redirect reused by other links');
    }

    // ---------------------------------------------------------------------------------------------------------
    // Bug 1: prefix match with no closing-quote anchor
    // ---------------------------------------------------------------------------------------------------------

    #[DataProvider('untrackedLongerUrlProvider')]
    #[TestDox('bug: a same-host link that is not tracked must not be hijacked by a tracked base URL')]
    public function testUntrackedLongerUrlIsNotHijackedByTrackedPrefix(string $untrackedUrl): void
    {
        $html = '<html><body>'
            .'<a href="'.self::BASE.'">home</a>'
            .'<a href="'.htmlspecialchars($untrackedUrl, ENT_QUOTES).'" data-mautic-disable-tracking="true">skip</a>'
            .'</body></html>';

        [$content, $trackables] = $this->parse($html);

        $this->assertCount(1, $trackables, 'Only the base link should be tracked');
        $this->assertContains(
            htmlspecialchars_decode($untrackedUrl),
            array_map('htmlspecialchars_decode', $this->hrefs($content)),
            'The disable-tracking link must keep its original href'
        );
    }

    /**
     * @param string[] $tracked URLs that are tracked
     * @param string   $other   URL that begins with a tracked URL but is its own distinct link (untracked here)
     */
    #[DataProvider('prefixSharingUrlProvider')]
    #[TestDox('bug: replacement must match the whole href, not just its beginning')]
    public function testReplacementMatchesWholeHrefValue(array $tracked, string $other): void
    {
        $html = '';
        foreach ($tracked as $url) {
            $html .= '<a href="'.htmlspecialchars($url, ENT_QUOTES).'">t</a>';
        }
        $html .= '<a href="'.htmlspecialchars($other, ENT_QUOTES).'" data-mautic-disable-tracking="true">o</a>';

        [$content] = $this->parse('<html><body>'.$html.'</body></html>');

        $this->assertContains($other, array_map('htmlspecialchars_decode', $this->hrefs($content)));
    }

    #[DataProvider('paddedHrefProvider')]
    #[TestDox('control: spaces or line breaks inside the href quotes do not stop a tracked URL from being replaced')]
    public function testPaddedHrefIsStillReplaced(string $padding): void
    {
        $html = '<html><body><a href="'.$padding.self::BASE.'projects'.$padding.'">projects</a></body></html>';

        [$content, $trackables] = $this->parse($html);

        $this->assertCount(1, $trackables);
        $this->assertSame([array_key_first($trackables)], $this->hrefs($content));
    }

    #[DataProvider('earlierAttributeProvider')]
    #[TestDox('control: a quoted attribute before href that contains "<" or ">" does not stop the link from being replaced')]
    public function testEarlierAttributeContainingAngleBracketsIsTolerated(string $attribute): void
    {
        $html = '<html><body><a '.$attribute.' href="'.self::BASE.'projects">projects</a></body></html>';

        [$content, $trackables] = $this->parse($html);

        $this->assertCount(1, $trackables);
        $this->assertStringContainsString(' href="'.array_key_first($trackables).'"', $content);
        $this->assertStringNotContainsString(self::BASE.'projects', $content);
    }

    #[TestDox('bug: tracked href remainder is never concatenated after a {trackable} token')]
    public function testNoTokenIsFollowedByLeakedHrefRemainder(): void
    {
        $encode = static fn (string $url): string => str_replace('=', '&#61;', $url);

        [$content] = $this->parse($this->buildEmail(self::EMAIL_URLS, $encode));

        $this->assertDoesNotMatchRegularExpression('/\{trackable=[^}]+\}[^"\'>\s]/', $content);
    }

    // ---------------------------------------------------------------------------------------------------------
    // Data providers
    // ---------------------------------------------------------------------------------------------------------

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function earlierAttributeProvider(): \Iterator
    {
        yield 'greater-than in double quotes' => ['title="a > b"'];
        yield 'less-than in single quotes' => ["title='a < b'"];
        yield 'both with a second attribute' => ['title="a > b" class="x"'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function paddedHrefProvider(): \Iterator
    {
        yield 'spaces' => ['  '];
        yield 'line break' => ["\n"];
        yield 'line break and indentation' => ["\r\n    "];
    }

    /**
     * @return array<string, array{0: string, 1: callable}>
     */
    public static function encodingProvider(): array
    {
        return [
            'equals as &#61;'                   => ['equals as &#61;', static fn (string $u): string => str_replace(['&', '='], ['&amp;', '&#61;'], $u)],
            'equals as &#x3D;'                  => ['equals as &#x3D;', static fn (string $u): string => str_replace(['&', '='], ['&amp;', '&#x3D;'], $u)],
            'equals as &#x3d; (lowercase)'      => ['equals as &#x3d;', static fn (string $u): string => str_replace(['&', '='], ['&amp;', '&#x3d;'], $u)],
            'equals as &#61; with bare &'       => ['equals as &#61; bare &', static fn (string $u): string => str_replace('=', '&#61;', $u)],
            'ampersand as &#38;'                => ['ampersand as &#38;', static fn (string $u): string => str_replace('&', '&#38;', $u)],
            'ampersand as &#x26;'               => ['ampersand as &#x26;', static fn (string $u): string => str_replace('&', '&#x26;', $u)],
            'question mark as &#63;'            => ['question mark as &#63;', static fn (string $u): string => str_replace('?', '&#63;', $u)],
            'slash as &#47; in path'            => ['slash as &#47;', static fn (string $u): string => str_replace('/projects/', '&#47;projects&#47;', $u)],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function untrackedLongerUrlProvider(): array
    {
        return [
            'path'                    => ['https://www.example.com/projects'],
            'path with query'         => ['https://www.example.com/projects?tab=suggested'],
            'long query'              => ['https://www.example.com/projects/G1?utm_source=mautic&utm_medium=email'],
            'fragment'                => ['https://www.example.com/#pricing'],
            'trailing path segment'   => ['https://www.example.com/terms-and-conditions'],
        ];
    }

    /**
     * @return array<string, array{0: string[], 1: string}>
     */
    public static function prefixSharingUrlProvider(): array
    {
        return [
            'host root vs path'           => [['https://foo.example.com/'], 'https://foo.example.com/a'],
            'no slash vs path'            => [['https://foo.example.com'], 'https://foo.example.com/a/b'],
            'path vs longer path'         => [['https://foo.example.com/a'], 'https://foo.example.com/a/b'],
            'path vs sibling with suffix' => [['https://foo.example.com/a'], 'https://foo.example.com/abc'],
            'query vs extended query'     => [['https://foo.example.com/a?x=1'], 'https://foo.example.com/a?x=1&y=2'],
            'url vs url with fragment'    => [['https://foo.example.com/a'], 'https://foo.example.com/a#frag'],
        ];
    }

    // ---------------------------------------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------------------------------------

    /**
     * @param string[] $urls
     */
    private function buildEmail(array $urls, callable $encodeHref): string
    {
        $links = '';
        foreach ($urls as $url) {
            $links .= '<p><a href="'.$encodeHref($url).'" target="_blank">link</a></p>'."\n";
        }

        return '<html><head><title>t</title></head><body>'."\n".$links.'</body></html>';
    }

    /**
     * @return array{0: string, 1: array<string, Trackable>}
     */
    private function parse(string $html): array
    {
        return $this->getModel()->parseContentForTrackables($html, [], 'email', 1);
    }

    /**
     * @return string[] raw href attribute values in document order
     */
    private function hrefs(string $html): array
    {
        preg_match_all('/<a\b[^>]*?\shref=(["\'])(.*?)\1/is', $html, $m);

        return $m[2];
    }

    /**
     * @param array<string, Trackable> $trackables keyed by {trackable=ID}
     */
    private function tokenFor(array $trackables, string $url): string
    {
        foreach ($trackables as $token => $trackable) {
            if ($trackable->getRedirect()->getUrl() === $url) {
                return (string) $token;
            }
        }

        $this->fail("No trackable registered for $url");
    }

    /**
     * Every href must be EXACTLY one {trackable=ID} token (no raw URL, no leaked remainder) and every registered
     * trackable must be used by exactly one link (no two destinations sharing one redirect).
     *
     * @param array<string, Trackable> $trackables
     */
    private function assertEveryHrefIsExactlyOneOwnToken(string $content, array $trackables, int $expectedLinks, string $message = ''): void
    {
        $hrefs = $this->hrefs($content);
        $this->assertCount($expectedLinks, $hrefs, $message);

        foreach ($hrefs as $href) {
            $this->assertMatchesRegularExpression('/^\{trackable=[0-9a-f]+\}$/', $href, trim($message.' href is not exactly one token: '.$href));
        }

        $this->assertCount(
            count($trackables),
            array_unique($hrefs),
            trim($message.' two or more different destinations were given the same redirect token')
        );
    }

    /**
     * @return TrackableModel&MockObject
     */
    private function getModel(): MockObject
    {
        $model = $this->getMockBuilder(TrackableModel::class)
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

        $model->autowireTrackableModel(
            $this->createStub(RedirectModel::class),
            $this->createStub(LeadFieldRepository::class),
            $this->createStub(TrackableRepository::class)
        );

        $model->method('getDoNotTrackList')->willReturn(['{webview_url}', '{unsubscribe_url}', '{trackable=(.*?)}']);
        $model->method('getContactFieldUrlTokens')->willReturn([]);
        $model->method('getEntitiesFromUrls')->willReturnCallback(
            function (array $trackableUrls): array {
                $entities = [];
                foreach ($trackableUrls as $url) {
                    $redirect = new Redirect();
                    $redirect->setUrl($url);
                    $redirect->setRedirectId();

                    $trackable = new Trackable();
                    $trackable->setChannel('email')->setChannelId(1)->setRedirect($redirect);
                    $entities[$url] = $trackable;
                }

                return $entities;
            }
        );

        return $model;
    }
}
