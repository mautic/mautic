<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\EmailAddressHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailAddressHelperTest extends TestCase
{
    private EmailAddressHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new EmailAddressHelper();
    }

    #[DataProvider('emailProvider')]
    public function testCleanEmail(string $email, string $expected): void
    {
        $this->assertSame($expected, $this->helper->cleanEmail($email));
    }

    /**
     * @return \Iterator<int, array<int, string>>
     */
    public static function emailProvider(): \Iterator
    {
        yield ['test@example.com', 'test@example.com'];
        yield ['TEST@example.com', 'test@example.com'];
        yield ['test+suffix@example.com', 'test+suffix@example.com'];
        yield ['!#$%^&*()@example.com', '@example.com'];
    }

    /**
     * @param array<int, string> $expected
     */
    #[DataProvider('variationsProvider')]
    public function testGetVariations(string $email, array $expected): void
    {
        $this->assertSame(
            $expected,
            $this->helper->getVariations($email)
        );
    }

    /**
     * @return \Iterator<int, array<int, (array<int, string>|string)>>
     */
    public static function variationsProvider(): \Iterator
    {
        yield ['test@example.com', ['test@example.com']];
        yield ['TEST@example.com', ['TEST@example.com', 'test@example.com']];
        yield ['test+suffix@example.com', ['test+suffix@example.com', 'test@example.com']];
        yield ['!#$%^&*()@example.com', ['!#$%^&*()@example.com', '@example.com']];
    }
}
