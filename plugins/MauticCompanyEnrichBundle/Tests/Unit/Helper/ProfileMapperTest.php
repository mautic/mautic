<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Tests\Unit\Helper;

use MauticPlugin\MauticCompanyEnrichBundle\Helper\ProfileMapper;
use PHPUnit\Framework\TestCase;

final class ProfileMapperTest extends TestCase
{
    public function testOnlyProfileFieldsCanBeChanged(): void
    {
        $data = ['first_name' => 'Alex', 'last_name' => 'Example', 'position' => 'Engineer', 'email' => 'new@example.com', 'doNotContact' => false, 'location' => ['city' => ['name' => 'Berlin'], 'country' => ['name' => 'Germany']]];
        $this->assertSame(['firstname' => 'Alex', 'lastname' => 'Example', 'position' => 'Engineer', 'city' => 'Berlin', 'country' => 'Germany'], ProfileMapper::fields('contact', $data));
    }

    public function testCompanyRangesAreNotInventedAsExactNumbers(): void
    {
        $this->assertSame(['companyname' => 'Example'], ProfileMapper::fields('company', ['name' => 'Example', 'employees' => '51-200', 'revenue' => '1m-10m', 'description' => null]));
    }
}
