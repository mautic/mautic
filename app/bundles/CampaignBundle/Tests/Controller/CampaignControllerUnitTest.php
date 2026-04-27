<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Controller\CampaignController;
use PHPUnit\Framework\TestCase;

class CampaignControllerUnitTest extends TestCase
{
    public function testNormalizeCampaignSourcesSkipsNonArrayAndNonNumericEntries(): void
    {
        $controller = $this->getMockBuilder(CampaignController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflectionMethod = new \ReflectionMethod(CampaignController::class, 'normalizeCampaignSources');
        $normalized       = $reflectionMethod->invoke($controller, [
            'lists'         => [
                '12'       => true,
                'list-one' => true,
            ],
            'forms'         => [
                '7'        => 'Form 7',
                'form-one' => 'Ignored form',
            ],
            'not-an-array'  => 'ignored',
        ]);

        $this->assertSame([
            'lists' => [
                12 => true,
            ],
            'forms' => [
                7 => 'Form 7',
            ],
        ], $normalized);
    }
}
