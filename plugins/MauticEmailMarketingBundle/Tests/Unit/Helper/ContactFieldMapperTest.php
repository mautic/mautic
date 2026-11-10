<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContactFieldMapperTest extends TestCase
{
    /**
     * @param array<mixed>          $leadFields
     * @param array<string, string> $expected
     */
    #[DataProvider('mappingProvider')]
    public function testNormalize(array $leadFields, array $expected): void
    {
        $this->assertSame($expected, ContactFieldMapper::normalize($leadFields));
    }

    /**
     * @return iterable<string, array{array<mixed>, array<string, string>}>
     */
    public static function mappingProvider(): iterable
    {
        yield 'current format, unmapped fields left out' => [
            ['EMAIL' => 'email', 'FNAME' => 'firstname', 'LNAME' => null, 'PHONE' => ''],
            ['EMAIL' => 'email', 'FNAME' => 'firstname'],
        ];

        yield 'former field matching format' => [
            ['list' => 'list-1', 'doubleOptin' => '1', 'sendWelcome' => '0', 'i_1' => 'EMAIL', 'm_1' => 'email', 'EMAIL' => '1', 'i_2' => 'FNAME', 'm_2' => 'firstname', 'FNAME' => '2'],
            ['EMAIL' => 'email', 'FNAME' => 'firstname'],
        ];

        yield 'nothing mapped' => [[], []];
    }

    public function testMapResolvesContactFieldsAndSpecialValues(): void
    {
        $contact = $this->createStub(Lead::class);
        $contact->method('getId')->willReturn(42);
        $contact->method('getProfileFields')->willReturn([
            'email'     => 'john@example.com',
            'firstname' => '<b>John</b> &amp; Co',
            'lastname'  => '',
        ]);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('mautic_plugin_timeline_view', ['integration' => 'Mailchimp', 'leadId' => 42], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://mautic.example/timeline');

        $doNotContactModel = $this->createStub(DoNotContactModel::class);
        $doNotContactModel->method('isContactable')->willReturn(DoNotContact::UNSUBSCRIBED);

        $mapper = new ContactFieldMapper($router, $doNotContactModel);

        $this->assertSame(
            [
                'EMAIL'    => 'john@example.com',
                'FNAME'    => 'John & Co',
                'MAUTICID' => '42',
                'TIMELINE' => 'https://mautic.example/timeline',
                'DNC'      => '1',
            ],
            $mapper->map($contact, [
                'EMAIL'    => 'email',
                'FNAME'    => 'firstname',
                'LNAME'    => 'lastname',
                'COMPANY'  => 'company',
                'MAUTICID' => ContactFieldMapper::CONTACT_ID,
                'TIMELINE' => ContactFieldMapper::CONTACT_TIMELINE_LINK,
                'DNC'      => ContactFieldMapper::CONTACT_IS_CONTACTABLE_BY_EMAIL,
            ], 'Mailchimp')
        );
    }
}
