<?php

namespace Mautic\LeadBundle\Tests\Twig;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class DncReasonHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array<int, string>
     */
    private $reasonTo = [
        DoNotContact::IS_CONTACTABLE => 'mautic.lead.event.donotcontact_contactable',
        DoNotContact::UNSUBSCRIBED   => 'mautic.lead.event.donotcontact_unsubscribed',
        DoNotContact::BOUNCED        => 'mautic.lead.event.donotcontact_bounced',
        DoNotContact::MANUAL         => 'mautic.lead.event.donotcontact_manual',
    ];

    /**
     * @var array<string, string>
     */
    private $translations = [
        'mautic.lead.event.donotcontact_contactable'  => 'a',
        'mautic.lead.event.donotcontact_unsubscribed' => 'b',
        'mautic.lead.event.donotcontact_bounced'      => 'c',
        'mautic.lead.event.donotcontact_manual'       => 'd',
    ];

    public function testToText(): void
    {
        foreach ($this->reasonTo as $reasonId => $translationKey) {
            $translationResult = $this->translations[$translationKey];

            $translator = $this->createMock(TranslatorInterface::class);
            $translator->expects($this->once())
                ->method('trans')
                ->with($translationKey)
                ->willReturn($translationResult);

            $dncReasonHelper = new DncReasonHelper($translator);

            $this->assertSame($translationResult, $dncReasonHelper->toText($reasonId));
        }

        $translator      = $this->createMock(TranslatorInterface::class);
        $dncReasonHelper = new DncReasonHelper($translator);
        $this->expectException(UnknownDncReasonException::class);
        $dncReasonHelper->toText(999);
    }

    public function testGetName(): void
    {
        $translator      = $this->createMock(TranslatorInterface::class);
        $dncReasonHelper = new DncReasonHelper($translator);
        $this->assertSame('lead_dnc_reason', $dncReasonHelper->getName());
    }
}
