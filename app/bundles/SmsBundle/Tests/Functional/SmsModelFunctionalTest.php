<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;

final class SmsModelFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntitiesTrait;

    /**
     * @dataProvider smsTranslationDataProvider
     */
    public function testSmsTranslationBasedOnLocale(string $contactLocale, string $expectedMessage): void
    {
        // 1. Create SMS with translation
        $sms   = $this->createAnSms('English SMS', 'Hello');
        $smsFr = $this->createAnSms('French SMS', 'Bonjour', true, 'fr_FR');
        $smsFr->setTranslationParent($sms);

        $this->em->persist($sms);
        $this->em->persist($smsFr);

        // 2. Create contact
        $contact = new Lead();
        $contact->setFirstname('Test');
        $contact->setLastname('Contact');
        $contact->setMobile('123456789');
        $this->em->persist($contact);
        $this->em->flush();

        $contactId = $contact->getId();

        // Clear the EM and fetch the entities
        $this->em->clear();
        $contact = $this->em->find(Lead::class, $contactId);
        $sms     = $this->em->find(Sms::class, $sms->getId());

        // Set locale
        $contact->addUpdatedField('preferred_locale', $contactLocale);
        $this->em->persist($contact);
        $this->em->flush();

        // 3. Mock transport
        $transportMock = $this->createMock(TransportChain::class);
        $transportMock->expects($this->once())
            ->method('sendSms')
            ->with(
                $this->anything(),
                $this->callback(function (string $message) use ($expectedMessage) {
                    $this->assertSame($expectedMessage, $message);

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(true);

        $this->getContainer()->set('mautic.sms.transport_chain', $transportMock);

        /** @var SmsModel $smsModel */
        $smsModel = $this->getContainer()->get('mautic.sms.model.sms');

        // 4. Send SMS
        $smsModel->sendSms($sms, $contact);
    }

    /**
     * @return iterable<string, string[]>
     */
    public static function smsTranslationDataProvider(): iterable
    {
        yield 'translation exists' => ['fr_FR', 'Bonjour'];
        yield 'translation not available (fallback)' => ['de_DE', 'Hello'];
    }
}
