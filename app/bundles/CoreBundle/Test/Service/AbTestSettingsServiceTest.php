<?php

namespace Mautic\CoreBundle\Test\Service;

use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\TestCase;

class AbTestSettingsServiceTest extends TestCase
{
    /**
     * Tests that service is returning proper AB test settings for a parent variant.
     */
    public function testGetAbTestSettings(): void
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $sendWinnerDelay = 2;
        $totalWeight     = 10;
        $weightA         = 33;
        $weightB         = 33;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setVariantSettings([
            'totalWeight'     => $totalWeight,
            'winnerCriteria'  => $winnerCriteria,
            'sendWinnerDelay' => $sendWinnerDelay, ]);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantB->method('getId')
            ->willReturn($idB);
        $parent->addVariantChild($variantB);
        $variantB->setVariantParent($parent);
        $variantB->setVariantSettings(['weight' => $weightB]);
        $variantB->setIsPublished(true);

        $abTestSettings = $abTestSettingsService->getAbTestSettings($parent);

        $this->assertEquals($totalWeight, $abTestSettings['totalWeight']);
        $this->assertEquals($winnerCriteria, $abTestSettings['winnerCriteria']);
        $this->assertEquals($sendWinnerDelay, $abTestSettings['sendWinnerDelay']);

        $onePercentOfTotalWeight = $totalWeight / 100;
        $weightACalculated       = round($onePercentOfTotalWeight * $weightA);
        $weightBCalculated       = round($onePercentOfTotalWeight * $weightB);
        $parentWeight            = $totalWeight - $weightACalculated - $weightBCalculated;
        $this->assertEquals($parentWeight, $abTestSettings['variants'][$idParent]['weight']);
        $this->assertEquals($weightACalculated, $abTestSettings['variants'][$idA]['weight']);
        $this->assertEquals($weightBCalculated, $abTestSettings['variants'][$idB]['weight']);

        $this->assertFalse($abTestSettings['configurationError']);
    }

    /**
     * Tests that in case of wrong configuration the service is returning configuration error.
     */
    public function testGetAbTestSettingsWrongConfiguration(): void
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $sendWinnerDelay = 2;
        $totalWeight     = 10; // wrong configuration, smaller than variant weights
        $weightA         = 65;
        $weightB         = 40;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setVariantSettings([
            'totalWeight'     => $totalWeight,
            'winnerCriteria'  => $winnerCriteria,
            'sendWinnerDelay' => $sendWinnerDelay, ]);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantB->method('getId')
            ->willReturn($idB);
        $parent->addVariantChild($variantB);
        $variantB->setVariantParent($parent);
        $variantB->setVariantSettings(['weight' => $weightB]);
        $variantB->setIsPublished(true);

        $abTestSettings = $abTestSettingsService->getAbTestSettings($parent);

        $this->assertTrue($abTestSettings['configurationError']);
    }

    /**
     * Tests settings for winner criteria if winnerCriteria is set in a children variant.
     */
    public function testGetAbTestChildrenSettings(): void
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $totalWeight     = 10;
        $weightA         = 15;
        $weightB         = 25;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA, 'winnerCriteria' => $winnerCriteria]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $variantB->method('getId')
            ->willReturn($idB);
        $parent->addVariantChild($variantB);
        $variantB->setVariantParent($parent);
        $variantB->setVariantSettings(['weight' => $weightB, 'winnerCriteria' => $winnerCriteria]);
        $variantB->setIsPublished(true);

        $abTestSettings = $abTestSettingsService->getAbTestSettings($parent);

        $onePercentOfTotalWeight = $totalWeight / 100;
        $weightACalculated       = round($onePercentOfTotalWeight * $weightA);
        $weightBCalculated       = round($onePercentOfTotalWeight * $weightB);
        $parentWeight            = $totalWeight - $weightACalculated - $weightBCalculated;

        $this->assertEquals($totalWeight, $abTestSettings['totalWeight']);
        $this->assertEquals($winnerCriteria, $abTestSettings['winnerCriteria']);

        $this->assertEquals($parentWeight, $abTestSettings['variants'][$idParent]['weight']);
        $this->assertEquals($weightACalculated, $abTestSettings['variants'][$idA]['weight']);
        $this->assertEquals($weightBCalculated, $abTestSettings['variants'][$idB]['weight']);

        $this->assertFalse($abTestSettings['configurationError']);
    }
}
