<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Api;

use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Integration\DataGeneratorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(ConnectwiseApi::class)]
final class ConnectwiseApiTest extends \PHPUnit\Framework\TestCase
{
    use DataGeneratorTrait;

    /**
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    #[TestDox('Tests that fetchAllRecords loops until all records are obtained')]
    public function testResultPagination(): void
    {
        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['makeRequest', 'getApiUrl'])
            ->getMock();

        $page = 0;
        $integration->expects($this->exactly(3))
            ->method('makeRequest')
            ->willReturnCallback(
                function ($endpoint, $parameters) use (&$page): array {
                    ++$page;

                    // Page should be incremented 3 times by fetchAllRecords method
                    $this->assertEquals(['page' => $page, 'pageSize' => ConnectwiseIntegration::PAGESIZE], $parameters);

                    return $this->generateData(3);
                }
            );

        $api = new ConnectwiseApi($integration);

        $records = $api->fetchAllRecords('test');

        $this->assertEquals($this->generatedRecords, $records);
    }
}
