<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testTrackingImageAction(): void
    {
        $this->client->request('GET', '/mtracking.gif?url=http%3A%2F%2Fmautic.org');

        $this->assertResponseStatusCodeSame(200);
    }

    #[DataProvider('xssPayloadsProvider')]
    public function testContactTrackingTagsXss(string $payload, ?string $expectedSanitized): void
    {
        $this->logoutUser();

        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('XSS Test');
        $page->setAlias('xss-test');
        $page->setCustomHtml('xss-test');
        $this->em->persist($page);
        $this->em->flush();

        $encodedPayload = urlencode($payload);
        $this->client->request(Request::METHOD_GET, "/xss-test?tags={$encodedPayload}");
        self::assertResponseIsSuccessful();

        $tagRepository = $this->em->getRepository(Tag::class);
        $tags          = $tagRepository->findAll();

        if ($expectedSanitized) {
            // Assert that a tag was created
            $this->assertCount(1, $tags);

            // Get the created tag
            $tag = $tags[0];

            // Assert that the tag name does not contain the malicious script
            $this->assertStringNotContainsString('<script>', $tag->getTag());
            $this->assertStringNotContainsString('</script>', $tag->getTag());

            // Assert that the tag name has been properly sanitized
            $this->assertEquals($expectedSanitized, $tag->getTag());
        } else {
            // Assert that a tag was NOT created
            $this->assertCount(0, $tags);
        }

        // Check the response content to ensure no script is present
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString($payload, (string) $content);
    }

    /**
     * @return \Iterator<string, array<int, (string|null)>>
     */
    public static function xssPayloadsProvider(): \Iterator
    {
        yield 'Basic script tag' => [
            '<script>alert(1)</script>',
            'alert(1)',
        ];
        yield 'Script tag with attributes' => [
            '<script src="http://example.com/evil.js"></script>',
            null,
        ];
        yield 'Encoded script tag' => [
            '&#60;script&#62;alert(1)&#60;/script&#62;',
            'alert(1)',
        ];
        yield 'On-event handler' => [
            '<img src="x" onerror="alert(1)">',
            null,
        ];
        yield 'JavaScript protocol in URL' => [
            '<a href="javascript:alert(1)">Click me</a>',
            'Click me',
        ];
        yield 'SVG with embedded script' => [
            '<svg><script>alert(1)</script></svg>',
            'alert(1)',
        ];
        yield 'CSS expression' => [
            '<div style="background:url(javascript:alert(1))">',
            null,
        ];
        yield 'Malformed tag' => [
            '<img """><script>alert("XSS")</script>"<',
            'alert("XSS")"',
        ];
        yield 'Malformed tag2' => [
            '<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">',
            null,
        ];
        yield 'Unicode escape' => [
            '<script>\u0061lert(1)</script>',
            '\u0061lert(1)',
        ];
    }

    public function testMtcEventCompanyXss(): void
    {
        $this->logoutUser();

        $this->client->request('POST', '/mtc/event', [
            'page_url' => 'https://example.com?Company=%3Cimg+src+onerror%3Dalert%28%27Company%27%29%3E',
        ]);
        $this->assertResponseIsSuccessful();

        $this->loginUser($this->em->getRepository(User::class)->findOneBy(['username' => 'admin']));

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', sprintf('/s/contacts/view/%d', $response['id']));
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringNotContainsString('<img src onerror=alert(\'Company\')>', (string) $content);

        $crawler = $this->client->request('GET', sprintf('/s/contacts/edit/%d', $response['id']));
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();

        $this->assertStringNotContainsString('<img src onerror=alert(\'Company\')>', (string) $content);

        $buttonCrawlerNode = $crawler->selectButton('Save & Close');
        $this->assertCount(1, $buttonCrawlerNode, $crawler->html());
        $form = $buttonCrawlerNode->form();
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('<img src onerror=alert(\'Company\')>', (string) $content);
    }

    public function testPageHitWithGpcHeaderDoesNotTrack(): void
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setTitle('Privacy landing page');
        $page->setAlias('privacy-landing-page');
        $page->setCustomHtml('<html><body>Privacy test</body></html>');
        $this->em->persist($page);
        $this->em->flush();

        $this->logoutUser();

        $this->client->request(Request::METHOD_GET, '/privacy-landing-page', [], [], ['HTTP_SEC_GPC' => '1']);
        $this->assertResponseIsSuccessful();

        $hit = $this->em->getRepository(Hit::class)->findOneBy(['page' => $page]);
        $this->assertNotInstanceOf(Hit::class, $hit, 'Ordinary page visits with an active GPC signal must not be tracked.');
    }
}
