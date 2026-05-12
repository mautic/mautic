<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\UuidHelper;
use PHPUnit\Framework\TestCase;

class UuidHelperTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideValidUuids')]
    public function testIsValidUuidWithValidUuids(string $uuid): void
    {
        $this->assertTrue(UuidHelper::isValidUuid($uuid));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidUuids')]
    public function testIsValidUuidWithInvalidUuids(string $uuid): void
    {
        $this->assertFalse(UuidHelper::isValidUuid($uuid));
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideValidUuids(): iterable
    {
        // UUID v1 (time-based)
        yield 'UUID v1' => ['6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // UUID v2 (DCE Security)
        yield 'UUID v2' => ['000003e8-9dad-21d1-b245-00c04fd430c8'];

        // UUID v3 (MD5 hash)
        yield 'UUID v3' => ['6ba7b811-9dad-31d1-80b4-00c04fd430c8'];

        // UUID v4 (random)
        yield 'UUID v4 lowercase' => ['550e8400-e29b-41d4-a716-446655440000'];
        yield 'UUID v4 uppercase' => ['550E8400-E29B-41D4-A716-446655440000'];
        yield 'UUID v4 mixed case' => ['550e8400-E29B-41d4-A716-446655440000'];

        // UUID v5 (SHA-1 hash)
        yield 'UUID v5' => ['6ba7b812-9dad-51d1-80b4-00c04fd430c8'];

        // Additional valid examples
        yield 'UUID v4 example 1' => ['f47ac10b-58cc-4372-a567-0e02b2c3d479'];
        yield 'UUID v4 example 2' => ['9b2c3d4e-5f6a-4b7c-8d9e-0f1a2b3c4d5e'];
        yield 'UUID v1 example' => ['c9a646d3-9c61-11ec-b909-0242ac120002'];
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function provideInvalidUuids(): iterable
    {
        yield 'too short' => ['550e8400-e29b-41d4-a716'];
        yield 'too long' => ['550e8400-e29b-41d4-a716-446655440000-extra'];
        yield 'missing hyphens' => ['550e8400e29b41d4a716446655440000'];
        yield 'wrong hyphen positions' => ['550e8400-e29b41-d4a7-16446655440000'];
        yield 'invalid characters' => ['550e8400-e29b-41d4-a716-44665544000g'];
        yield 'invalid version (v0)' => ['550e8400-e29b-01d4-a716-446655440000'];
        yield 'invalid version (v6)' => ['550e8400-e29b-61d4-a716-446655440000'];
        yield 'invalid version (v7)' => ['550e8400-e29b-71d4-a716-446655440000'];
        yield 'invalid version (v8)' => ['550e8400-e29b-81d4-a716-446655440000'];
        yield 'invalid version (v9)' => ['550e8400-e29b-91d4-a716-446655440000'];
        yield 'invalid version (va)' => ['550e8400-e29b-a1d4-a716-446655440000'];
        yield 'invalid variant (first nibble)' => ['550e8400-e29b-41d4-0716-446655440000'];
        yield 'invalid variant (second nibble)' => ['550e8400-e29b-41d4-c716-446655440000'];
        yield 'invalid variant (third nibble)' => ['550e8400-e29b-41d4-d716-446655440000'];
        yield 'invalid variant (fourth nibble)' => ['550e8400-e29b-41d4-e716-446655440000'];
        yield 'invalid variant (fifth nibble)' => ['550e8400-e29b-41d4-f716-446655440000'];
        yield 'empty string' => [''];
        yield 'null string' => ['00000000-0000-0000-0000-000000000000'];
        yield 'spaces' => ['550e8400 e29b 41d4 a716 446655440000'];
        yield 'curly braces' => ['{550e8400-e29b-41d4-a716-446655440000}'];
        yield 'with urn prefix' => ['urn:uuid:550e8400-e29b-41d4-a716-446655440000'];
        yield 'completely invalid' => ['not-a-uuid-at-all'];
        yield 'special characters' => ['550e8400-e29b-41d4-a716-4466554400@!'];
    }
}
