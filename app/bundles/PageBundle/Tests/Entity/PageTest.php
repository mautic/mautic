<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Attributes\DataProvider;

final class PageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<string, array<int, mixed>> $changes
     */
    #[DataProvider('setIsPreferenceCenterDataProvider')]
    public function testSetIsPreferenceCenter(mixed $value, mixed $expected, array $changes): void
    {
        $page = new Page();
        $page->setIsPreferenceCenter($value);

        $this->assertSame($expected, $page->getIsPreferenceCenter());
        $this->assertSame($changes, $page->getChanges());
    }

    /**
     * @return iterable<array{0: mixed, 1: mixed, 2: array<string, array{0: mixed, 1: mixed}>}>
     */
    public static function setIsPreferenceCenterDataProvider(): iterable
    {
        yield [null, null, []];
        yield [true, true, ['isPreferenceCenter' => [null, true]]];
        yield [false, false, ['isPreferenceCenter' => [null, false]]];
        yield ['', false, ['isPreferenceCenter' => [null, false]]];
        yield [0, false, ['isPreferenceCenter' => [null, false]]];
        yield ['string', true, ['isPreferenceCenter' => [null, true]]];
    }

    /**
     * @param array<string, array<int, mixed>> $changes
     */
    #[DataProvider('setNoIndexDataProvider')]
    public function testSetNoIndex(mixed $value, mixed $expected, array $changes): void
    {
        $page = new Page();
        $page->setNoIndex($value);

        $this->assertSame($expected, $page->getNoIndex());
        $this->assertSame($changes, $page->getChanges());
    }

    /**
     * @return iterable<array{0: mixed, 1: mixed, 2: array<string, array{0: mixed, 1: mixed}>}>
     */
    public static function setNoIndexDataProvider(): iterable
    {
        yield [null, null, []];
        yield [true, true, ['noIndex' => [null, true]]];
        yield [false, false, ['noIndex' => [null, false]]];
        yield ['', false, ['noIndex' => [null, false]]];
        yield [0, false, ['noIndex' => [null, false]]];
        yield ['string', true, ['noIndex' => [null, true]]];
    }

    /**
     * Test setHeadScript and getHeadScript.
     */
    public function testSetHeadScript(): void
    {
        $script = '<script>console.log("test")';
        $page   = new Page();
        $page->setHeadScript($script);

        $this->assertEquals($script, $page->getHeadScript());
    }

    /**
     * Test setFooterScript and getFooterScript.
     */
    public function testSetFooterScript(): void
    {
        $script = '<script>console.log("test")';
        $page   = new Page();
        $page->setFooterScript($script);

        $this->assertEquals($script, $page->getFooterScript());
    }

    #[DataProvider('setIsDuplicateDataProvider')]
    public function testIsDuplicate(bool $isDuplicate): void
    {
        $page = new Page();
        $page->setIsDuplicate($isDuplicate);
        $this->assertIsBool($page->isDuplicate());
    }

    /**
     * @return iterable<array{bool}>
     */
    public static function setIsDuplicateDataProvider(): iterable
    {
        yield [true];
        yield [false];
    }
}
