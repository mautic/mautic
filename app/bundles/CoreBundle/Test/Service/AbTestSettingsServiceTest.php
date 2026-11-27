<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Service;

use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\TestCase;

final class AbTestSettingsServiceTest extends TestCase
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

    public function testGetAbTestSettingsPreservesExplicitWeightsAndDefaultsMissingWeights(): void
    {
        $service = new AbTestSettingsService();
        $parent  = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
        $parent->method('getId')->willReturn(1);
        $parent->setVariantSettings(['totalWeight' => 100]);
        $parent->setIsPublished(true);

        foreach ([2 => ['weight' => 30], 3 => ['weight' => 0], 4 => []] as $id => $settings) {
            $variant = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
            $variant->method('getId')->willReturn($id);
            $variant->setIsPublished(true);
            $variant->setVariantParent($parent);
            $variant->setVariantSettings($settings);
            $parent->addVariantChild($variant);
        }

        $settings = $service->getAbTestSettings($parent);

        $this->assertSame(30, $settings['variants'][2]['weight']);
        $this->assertSame(0, $settings['variants'][3]['weight']);
        $this->assertSame(25, $settings['variants'][4]['weight']);
        $this->assertSame(45, $settings['variants'][1]['weight']);
    }

    public function testGetAbTestSettingsTreatsNullWeightAsMissing(): void
    {
        $service = new AbTestSettingsService();
        $parent  = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
        $parent->method('getId')->willReturn(1);
        $parent->setVariantSettings(['totalWeight' => 100]);
        $parent->setIsPublished(true);

        foreach ([2 => ['weight' => null], 3 => ['weight' => 30]] as $id => $settings) {
            $variant = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
            $variant->method('getId')->willReturn($id);
            $variant->setIsPublished(true);
            $variant->setVariantParent($parent);
            $variant->setVariantSettings($settings);
            $parent->addVariantChild($variant);
        }

        $settings = $service->getAbTestSettings($parent);

        $this->assertFalse($settings['configurationError']);
        $this->assertSame(33, $settings['variants'][2]['weight']);
        $this->assertSame(30, $settings['variants'][3]['weight']);
        $this->assertSame(37, $settings['variants'][1]['weight']);
    }

    public function testGetAbTestSettingsRejectsNegativeExplicitWeights(): void
    {
        $service = new AbTestSettingsService();
        $parent  = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
        $parent->method('getId')->willReturn(1);
        $parent->setVariantSettings(['totalWeight' => 100]);
        $parent->setIsPublished(true);

        foreach ([2 => ['weight' => -10], 3 => ['weight' => 30]] as $id => $settings) {
            $variant = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
            $variant->method('getId')->willReturn($id);
            $variant->setIsPublished(true);
            $variant->setVariantParent($parent);
            $variant->setVariantSettings($settings);
            $parent->addVariantChild($variant);
        }

        $settings = $service->getAbTestSettings($parent);

        $this->assertTrue($settings['configurationError']);
        $this->assertSame(0, $settings['variants'][2]['weight']);
        $this->assertSame(30, $settings['variants'][3]['weight']);
        $this->assertSame(70, $settings['variants'][1]['weight']);
    }

    public function testGetAbTestSettingsRoundedWeightsStayWithinTotal(): void
    {
        $service = new AbTestSettingsService();
        $parent  = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
        $parent->method('getId')->willReturn(1);
        $parent->setVariantSettings(['totalWeight' => 10]);
        $parent->setIsPublished(true);

        foreach ([2 => 35, 3 => 35, 4 => 30] as $id => $weight) {
            $variant = $this->getMockBuilder(Email::class)->onlyMethods(['getId'])->getMock();
            $variant->method('getId')->willReturn($id);
            $variant->setIsPublished(true);
            $variant->setVariantParent($parent);
            $variant->setVariantSettings(['weight' => $weight]);
            $parent->addVariantChild($variant);
        }

        $settings = $service->getAbTestSettings($parent);

        $this->assertFalse($settings['configurationError']);
        $this->assertSame(0, $settings['variants'][1]['weight']);
        $this->assertSame(10, array_sum(array_column($settings['variants'], 'weight')));
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
