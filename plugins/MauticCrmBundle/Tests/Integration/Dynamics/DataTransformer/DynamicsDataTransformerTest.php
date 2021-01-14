<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Integration\Dynamics\DataTransformer;

use MauticPlugin\MauticCrmBundle\Integration\Dynamics\DataTransformer\DynamicsDataTransformer;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;
use PHPUnit\Framework\TestCase;

class DynamicsDataTransformerTest extends TestCase
{

    /**
     * @var DynamicsDataTransformer
     */
    private $dynamicsDataTransformer;

    protected function setUp(): void
    {
        $dynamicsIntegrationMock = $this->createMock(DynamicsIntegration::class);
        $dynamicsIntegrationMock->method('getAvailableLeadFields')->willReturn($this->getFields('leads'));
        $this->dynamicsDataTransformer = new DynamicsDataTransformer($dynamicsIntegrationMock);
    }

    public function testGetLookupReferencesToRemove()
    {
        $this->getPayload();

        self::assertCount(1, $this->dynamicsDataTransformer->getLookupReferencesToRemove());
        self::assertSame(['lookupFieldToRemove'], $this->dynamicsDataTransformer->getLookupReferencesToRemove());
    }


    public function testPayloadData()
    {

        $payloadData = $this->getPayload();
        self::assertCount(2, $payloadData);
        self::assertArrayNotHasKey('lookupFieldToRemove', $payloadData);
    }

    private function getPayload()
    {
        $data              = [
            'standardField'       => 'value',
            'lookupField'         => 1,
            'lookupFieldToRemove' => '',
        ];

        return $this->dynamicsDataTransformer->getData('leads', $data);
    }

    private function getFields(string $object)
    {
        $fields                           = [];
        $fields[$object]['standardField'] = [
        ];

        $fields[$object]['lookupField'] = [
            'target' => 'contact',
        ];

        $fields[$object]['lookupFieldToRemove'] = [
            'target' => 'contact',
        ];

        return $fields;
    }
}
