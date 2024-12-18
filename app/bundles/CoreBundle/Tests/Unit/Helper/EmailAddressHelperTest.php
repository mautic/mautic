<?php

declare(strict_types=1);

use Mautic\CoreBundle\Helper\EmailAddressHelper;
use PHPUnit\Framework\TestCase;

final class EmailAddressHelperTest extends TestCase
{
    private EmailAddressHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new EmailAddressHelper();
    }

    /**
     * @dataProvider emailProvider
     */
    public function testCleanEmail(string $email, string $expected): void
    {
        $this->assertSame($expected, $this->helper->cleanEmail($email));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function emailProvider(): array
    {
        return [
            ['test@example.com', 'test@example.com'],
            ['TEST@example.com', 'test@example.com'],
            ['test+suffix@example.com', 'test+suffix@example.com'],
            ['!#$%^&*()@example.com', '@example.com'],
        ];
    }

    /**
     * @param array<int, string> $expected
     *
     * @dataProvider variationsProvider
     */
    public function testGetVariations(string $email, array $expected): void
    {
        $this->assertSame(
            $expected,
            $this->helper->getVariations($email)
        );
    }

    /**
     * @return array<int, array<int, array<int, string>|string>>
     */
    public static function variationsProvider(): array
    {
        return [
            ['test@example.com', ['test@example.com']],
            ['TEST@example.com', ['TEST@example.com', 'test@example.com']],
            ['test+suffix@example.com', ['test+suffix@example.com', 'test@example.com']],
            ['!#$%^&*()@example.com', ['!#$%^&*()@example.com', '@example.com']],
        ];
    }
}
