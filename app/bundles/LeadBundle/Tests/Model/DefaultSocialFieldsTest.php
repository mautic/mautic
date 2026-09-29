<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;

final class DefaultSocialFieldsTest extends TestCase
{
    public function testDefaultSocialFieldsAreCurrent(): void
    {
        $socialFields = array_keys(array_filter(
            FieldModel::$coreFields,
            static fn (array $field): bool => 'social' === ($field['group'] ?? null),
        ));

        $this->assertSame(
            ['facebook', 'instagram', 'linkedin', 'tiktok', 'twitter', 'youtube'],
            $socialFields,
        );
    }
}
