<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\ThemeFavoritesHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ThemeFavoritesFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

    private const THEME = 'blank';

    #[DataProvider('dataForThemeTypes')]
    public function testThemeMovesBetweenTheSections(string $type, string $formUrl): void
    {
        $crawler = $this->requestForm($formUrl);
        $this->assertThemeIsInSection($crawler, 'others');
        $this->assertFavoritesPlaceholderIsVisible($crawler, true);

        $this->assertSame(['success' => 1, 'favorite' => true], $this->setThemeFavorite(self::THEME, $type, true));

        $crawler = $this->requestForm($formUrl);
        $this->assertThemeIsInSection($crawler, 'favorites');
        $this->assertFavoritesPlaceholderIsVisible($crawler, false);
        $this->assertCount(1, $crawler->filter('[data-theme-section="favorites"] [data-theme-favorite="'.self::THEME.'"] i.ri-star-fill'));

        $this->assertSame(['success' => 1, 'favorite' => false], $this->setThemeFavorite(self::THEME, $type, false));

        $crawler = $this->requestForm($formUrl);
        $this->assertThemeIsInSection($crawler, 'others');
        $this->assertFavoritesPlaceholderIsVisible($crawler, true);
        $this->assertCount(1, $crawler->filter('[data-theme-section="others"] [data-theme-favorite="'.self::THEME.'"] i.ri-star-line'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dataForThemeTypes(): iterable
    {
        yield 'email' => ['email', '/s/emails/new'];
        yield 'landing page' => ['page', '/s/pages/new'];
    }

    public function testFavoritesAreStoredPerType(): void
    {
        $this->assertSame(['success' => 1, 'favorite' => true], $this->setThemeFavorite(self::THEME, 'email', true));

        $this->assertThemeIsInSection($this->requestForm('/s/emails/new'), 'favorites');
        $this->assertThemeIsInSection($this->requestForm('/s/pages/new'), 'others');
    }

    public function testFavoritesAreStoredPerUser(): void
    {
        $this->assertSame(['success' => 1, 'favorite' => true], $this->setThemeFavorite(self::THEME, 'email', true));

        $user = $this->createUser('second.admin@mautic-test.com', 'second-admin', 'Second', 'Admin', $this->createRole('Second admin', true));
        $this->em->flush();
        $this->loginOtherUser($user);

        $crawler = $this->requestForm('/s/emails/new');
        $this->assertThemeIsInSection($crawler, 'others');
        $this->assertFavoritesPlaceholderIsVisible($crawler, true);
    }

    #[DataProvider('dataForInvalidFavorites')]
    public function testInvalidFavoriteIsRejected(string $theme, string $type): void
    {
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=setThemeFavorite',
            ['theme' => $theme, 'type' => $type, 'favorite' => 1]
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame(['success' => 0], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([], $this->getStoredFavorites());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dataForInvalidFavorites(): iterable
    {
        yield 'unsupported type' => [self::THEME, 'form'];
        yield 'unknown theme' => ['unknown-theme', 'email'];
        yield 'theme not installed for the type' => ['aurora', 'page'];
    }

    public function testRequestThatIsNotAjaxIsRejected(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/s/ajax?action=setThemeFavorite',
            ['theme' => self::THEME, 'type' => 'email', 'favorite' => 1]
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertSame([], $this->getStoredFavorites());
    }

    /**
     * @return array<string, mixed>
     */
    private function setThemeFavorite(string $theme, string $type, bool $favorite): array
    {
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=setThemeFavorite',
            ['theme' => $theme, 'type' => $type, 'favorite' => $favorite ? 1 : 0]
        );
        $this->assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function requestForm(string $formUrl): Crawler
    {
        $crawler = $this->client->request(Request::METHOD_GET, $formUrl);
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function assertThemeIsInSection(Crawler $crawler, string $section): void
    {
        $otherSection = 'favorites' === $section ? 'others' : 'favorites';

        $this->assertCount(1, $crawler->filter('[data-theme-section="'.$section.'"] [data-theme-favorite="'.self::THEME.'"]'));
        $this->assertCount(0, $crawler->filter('[data-theme-section="'.$otherSection.'"] [data-theme-favorite="'.self::THEME.'"]'));
    }

    private function assertFavoritesPlaceholderIsVisible(Crawler $crawler, bool $visible): void
    {
        $placeholder = $crawler->filter('[data-theme-favorites-placeholder]');
        $this->assertCount(1, $placeholder);

        $classes = explode(' ', (string) $placeholder->attr('class'));
        $this->assertSame($visible, !in_array('hide', $classes, true));
    }

    /**
     * @return array<mixed>
     */
    private function getStoredFavorites(): array
    {
        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);

        return $user->getPreferences()[ThemeFavoritesHelper::PREFERENCE_KEY] ?? [];
    }
}
