<?php


namespace Mautic\CoreBundle\Test\Service;

use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\EmailBundle\Entity\Email;

class AbTestSettingsServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that service is returning proper AB test settings for a parent variant.
     */
    public function testGetAbTestSettings()
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $sendWinnerDelay = 2;
        $totalWeight     = 50;
        $weightA         = 15;
        $weightB         = 25;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setVariantSettings([
            'totalWeight'     => $totalWeight,
            'winnerCriteria'  => $winnerCriteria,
            'sendWinnerDelay' => $sendWinnerDelay, ]);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
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

        $this->assertEquals($totalWeight - $weightA - $weightB, $abTestSettings['variants'][$idParent]['weight']);
        $this->assertEquals($weightA, $abTestSettings['variants'][$idA]['weight']);
        $this->assertEquals($weightB, $abTestSettings['variants'][$idB]['weight']);

        $this->assertFalse($abTestSettings['configurationError']);
    }

    /**
     * Tests that in case of wrong configuration the service is returning configuration error.
     */
    public function testGetAbTestSettingsWrongConfiguration()
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $sendWinnerDelay = 2;
        $totalWeight     = 50; // wrong configuration, smaller than variant weights
        $weightA         = 15;
        $weightB         = 40;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setVariantSettings([
            'totalWeight'     => $totalWeight,
            'winnerCriteria'  => $winnerCriteria,
            'sendWinnerDelay' => $sendWinnerDelay, ]);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
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
    public function testGetAbTestChildrenSettings()
    {
        $abTestSettingsService = new AbTestSettingsService();

        $winnerCriteria  = 'email.openrate';
        $totalWeight     = 100;
        $weightA         = 15;
        $weightB         = 25;

        $idParent = 1;
        $idA      = 2;
        $idB      = 3;

        $parent = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $parent->method('getId')
            ->willReturn($idParent);
        $parent->setIsPublished(true);

        $variantA = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variantA->method('getId')
            ->willReturn($idA);
        $parent->addVariantChild($variantA);
        $variantA->setVariantParent($parent);
        $variantA->setVariantSettings(['weight' => $weightA, 'winnerCriteria' => $winnerCriteria]);
        $variantA->setIsPublished(true);

        $variantB = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variantB->method('getId')
            ->willReturn($idB);
        $parent->addVariantChild($variantB);
        $variantB->setVariantParent($parent);
        $variantB->setVariantSettings(['weight' => $weightB, 'winnerCriteria' => $winnerCriteria]);
        $variantB->setIsPublished(true);

        $abTestSettings = $abTestSettingsService->getAbTestSettings($parent);

        $this->assertEquals($totalWeight, $abTestSettings['totalWeight']);
        $this->assertEquals($winnerCriteria, $abTestSettings['winnerCriteria']);

        $this->assertEquals($totalWeight - $weightA - $weightB, $abTestSettings['variants'][$idParent]['weight']);
        $this->assertEquals($weightA, $abTestSettings['variants'][$idA]['weight']);
        $this->assertEquals($weightB, $abTestSettings['variants'][$idB]['weight']);

        $this->assertFalse($abTestSettings['configurationError']);
    }
}
