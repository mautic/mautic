<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use PHPUnit\Framework\Assert;

class DynamicContentHelperFunctionalTest extends MauticMysqlTestCase
{
    public function testGetDwcsBySlotNameWithPublishedOnlyTrue(): void
    {
        // Create and persist test DynamicContent entities
        $dynamicContent1 = new DynamicContent();
        $dynamicContent1->setName('dwc 1');
        $dynamicContent1->setSlotName('test_slot');
        $dynamicContent1->setDisplayOrder(0);
        $dynamicContent1->setIsPublished(true);
        $dynamicContent1->setIsCampaignBased(false);
        $this->em->persist($dynamicContent1);

        $dynamicContent2 = new DynamicContent();
        $dynamicContent2->setName('dwc 2');
        $dynamicContent2->setSlotName('test_slot');
        $dynamicContent2->setDisplayOrder(1);
        $dynamicContent2->setIsPublished(false);
        $dynamicContent2->setIsCampaignBased(false);
        $this->em->persist($dynamicContent2);

        $this->em->flush();

        /** @var DynamicContentHelper $dynamicContentHelper */
        $dynamicContentHelper = self::$container->get('mautic.helper.dynamicContent');

        $content = '<body><div>{dwc=test_slot}{/dwc}</div></body>';
        $tokens  = $dynamicContentHelper->findDwcTokens($content);

        foreach ($tokens as $tokens) {
            foreach ($tokens as $varient) {
                Assert::assertTrue($varient->getIsPublished());
            }
        }
    }
}
