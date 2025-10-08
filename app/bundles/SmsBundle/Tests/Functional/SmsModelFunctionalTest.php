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
        $contact = $this->createLead('Test', 'Contact', '123456789');

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

    public function testSmsTranslationIsSentAndTrackedPerLocale(): void
    {
        // 1. Create SMS with translation
        $sms   = $this->createAnSms('English SMS', 'Hello');
        $smsFr = $this->createAnSms('French SMS', 'Bonjour', true, 'fr_FR');
        $smsFr->setTranslationParent($sms);

        $this->em->persist($sms);
        $this->em->persist($smsFr);

        // 2. Create contacts
        $englishLead = $this->createLead('User', 'English', '123456789');
        $frenchLead  = $this->createLead('User', 'French', '234567891');

        $this->em->flush();
        $this->em->clear();

        // 3. Reload entities
        $sms      = $this->em->find(Sms::class, $sms->getId());
        $smsFr    = $this->em->find(Sms::class, $smsFr->getId());
        $contact1 = $this->em->find(Lead::class, $englishLead->getId());
        $contact2 = $this->em->find(Lead::class, $frenchLead->getId());

        // 4. Update locale for the second contact
        $contact2->addUpdatedField('preferred_locale', 'fr_FR');
        $this->em->flush();

        // 5. Mock transport
        $expectedMessages = ['Hello', 'Bonjour'];
        $callIndex        = 0;

        $transportMock = $this->createMock(TransportChain::class);
        $transportMock->expects($this->exactly(2))
            ->method('sendSms')
            ->with(
                $this->anything(),
                $this->callback(function (string $message) use (&$callIndex, $expectedMessages) {
                    $this->assertSame($expectedMessages[$callIndex], $message);
                    ++$callIndex;

                    return true;
                }),
                $this->anything()
            )
            ->willReturn(true);

        $this->getContainer()->set('mautic.sms.transport_chain', $transportMock);

        /** @var SmsModel $smsModel */
        $smsModel = $this->getContainer()->get('mautic.sms.model.sms');

        // 6. Send SMS
        $results = $smsModel->sendSms($sms, [$contact1, $contact2]);
        $this->assertCount(2, $results);

        // 7. Validate SMS stats per contact
        $statRepo = $smsModel->getStatRepository();

        $stat1 = $statRepo->getLeadStats($contact1->getId());
        $this->assertSame((string) $sms->getId(), $stat1[0]['sms_id'], 'English contact should map to base SMS.');

        $stat2 = $statRepo->getLeadStats($contact2->getId());
        $this->assertSame((string) $smsFr->getId(), $stat2[0]['sms_id'], 'French contact should map to translated SMS.');
    }

    private function createLead(string $firstname, string $lastname, string $mobile): Lead
    {
        $contact = new Lead();
        $contact->setFirstname($firstname);
        $contact->setLastname($lastname);
        $contact->setMobile($mobile);
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }
}
