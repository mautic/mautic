<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Dynamics\DataTransformer\DTO;

use MauticPlugin\MauticCrmBundle\Integration\Dynamics\DataTransformer\DTO\FormattedValueDTO;
use PHPUnit\Framework\TestCase;

class FormattedValueDTOTest extends TestCase
{
    public function testIsLookupType()
    {
        $field = [
            'key'   => 'key',
            'value' => 'value',
        ];
        self::assertFalse($this->getFormattedValue($field)->isLookupType());

        $field['target'] = 'whatever';
        self::assertTrue($this->getFormattedValue($field)->isLookupType());
    }

    public function testGetKeyForPayload()
    {
        $field = [
            'key'   => 'key',
            'value' => 'value',
        ];
        self::assertEquals($field['key'], $this->getFormattedValue($field)->getKeyForPayload());

        $field['target'] = 'contact';
        self::assertEquals('key@odata.bind', $this->getFormattedValue($field)->getKeyForPayload());
    }

    public function testGetValueForPayload()
    {
        $field = [
            'key'   => 'lookup',
            'value' => 'value',
        ];
        self::assertEquals($field['value'], $this->getFormattedValue($field)->getValueForPayload());

        $field['target'] = 'contact';
        self::assertEquals('/contacts(value)', $this->getFormattedValue($field)->getValueForPayload());
    }

    private function getFormattedValue(array $field): FormattedValueDTO
    {
        return new FormattedValueDTO($field['key'], $field['value'], $field);
    }
}
