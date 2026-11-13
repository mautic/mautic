<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ThemeFavoritesHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeFavoritesHelperTest extends TestCase
{
    private const INSTALLED_THEMES = [
        'email' => ['aurora' => 'Aurora', 'blank' => 'Blank', 'brienz' => 'Brienz'],
        'page'  => ['blank' => 'Blank', 'capture' => 'Capture'],
    ];

    private User $user;

    private ThemeFavoritesHelper $helper;

    protected function setUp(): void
    {
        $this->user = new User();

        $userHelper = $this->createMock(UserHelper::class);
        $userHelper->method('getUser')->willReturnCallback(fn (): User => $this->user);

        $themeHelper = $this->createMock(ThemeHelperInterface::class);
        $themeHelper->method('getInstalledThemes')
            ->willReturnCallback(static fn (string $type): array => self::INSTALLED_THEMES[$type] ?? []);

        $this->helper = new ThemeFavoritesHelper($userHelper, $this->createStub(UserRepository::class), $themeHelper);
    }

    public function testFavoritesAreAddedInTheOrderOfTheInstalledThemes(): void
    {
        $this->helper->setFavorite('brienz', 'email', true);
        $this->helper->setFavorite('aurora', 'email', true);

        $this->assertSame(['aurora', 'brienz'], $this->helper->getFavorites('email'));
        $this->assertTrue($this->helper->isFavorite('aurora', 'email'));
        $this->assertFalse($this->helper->isFavorite('blank', 'email'));
    }

    public function testFavoriteIsRemoved(): void
    {
        $this->helper->setFavorite('aurora', 'email', true);
        $this->helper->setFavorite('blank', 'email', true);
        $this->helper->setFavorite('aurora', 'email', false);

        $this->assertSame(['blank'], $this->helper->getFavorites('email'));
    }

    public function testFavoriteIsNotAddedTwice(): void
    {
        $this->helper->setFavorite('blank', 'email', true);
        $this->helper->setFavorite('blank', 'email', true);

        $this->assertSame(['blank'], $this->helper->getFavorites('email'));
    }

    public function testFavoritesAreStoredPerType(): void
    {
        $this->helper->setFavorite('blank', 'email', true);

        $this->assertSame(['blank'], $this->helper->getFavorites('email'));
        $this->assertSame([], $this->helper->getFavorites('page'));
    }

    public function testOtherPreferencesAreKeptAndUninstalledThemesAreRemoved(): void
    {
        $this->user->setPreferences([
            'some_other_preference'                => 'value',
            ThemeFavoritesHelper::PREFERENCE_KEY => [
                'email' => ['deleted-theme', 'blank'],
                'page'  => ['capture'],
            ],
        ]);

        $this->helper->setFavorite('aurora', 'email', true);

        $this->assertSame(
            [
                'some_other_preference'                => 'value',
                ThemeFavoritesHelper::PREFERENCE_KEY => [
                    'email' => ['aurora', 'blank'],
                    'page'  => ['capture'],
                ],
            ],
            $this->user->getPreferences()
        );
    }

    /**
     * @param mixed[] $preferences
     */
    #[DataProvider('dataForInvalidStoredPreferences')]
    public function testInvalidStoredPreferencesGiveNoFavorites(array $preferences): void
    {
        $this->user->setPreferences($preferences);

        $this->assertSame([], $this->helper->getFavorites('email'));
    }

    /**
     * @return iterable<string, array{mixed[]}>
     */
    public static function dataForInvalidStoredPreferences(): iterable
    {
        yield 'no favorites' => [[]];
        yield 'favorites are a string' => [[ThemeFavoritesHelper::PREFERENCE_KEY => 'blank']];
        yield 'favorites of the type are a string' => [[ThemeFavoritesHelper::PREFERENCE_KEY => ['email' => 'blank']]];
    }

    public function testUnsupportedTypeGivesNoFavorites(): void
    {
        $this->user->setPreferences([ThemeFavoritesHelper::PREFERENCE_KEY => ['form' => ['blank']]]);

        $this->assertSame([], $this->helper->getFavorites('form'));
    }

    #[DataProvider('dataForInvalidFavorite')]
    public function testInvalidFavoriteIsRejected(string $theme, string $type): void
    {
        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->helper->setFavorite($theme, $type, true);
        } finally {
            $this->assertSame([], $this->user->getPreferences());
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dataForInvalidFavorite(): iterable
    {
        yield 'unsupported type' => ['blank', 'form'];
        yield 'unknown theme' => ['unknown-theme', 'email'];
        yield 'theme not installed for the type' => ['aurora', 'page'];
        yield 'empty theme' => ['', 'email'];
    }
}
