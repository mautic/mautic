<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeduplicateCommand;
use Mautic\LeadBundle\Deduplicate\ContactDeduper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Assert;

final class DeduplicateCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testDeduplicateCommand(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        $contactDeduper = self::$container->get('mautic.lead.deduper');
        \assert($contactDeduper instanceof ContactDeduper);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');

        $this->saveContact('john@doe.email'); // 1
        $this->saveContact('john@doe.email'); // 1
        $this->saveContact('john@doe.email'); // 1
        $this->saveContact('john@doe.email'); // 1
        $this->saveContact('anna@munic.email'); // 2
        $this->saveContact('anna@munic.email'); // 2
        $this->saveContact('jane@gabriel.email'); // 3

        $this->em->flush();

        Assert::assertSame(7, $contactRepository->count([]));

        Assert::assertSame(
            2,
            $contactDeduper->countDuplicatedContacts(array_keys($contactDeduper->getUniqueFields('lead'))),
            'The deduper should see and process only 2 duplicated contacts. The third is unique.'
        );

        $this->runCommand(DeduplicateCommand::NAME);

        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(3, $contactRepository->count([]));
    }

    public function testDeduplicateCommandWithAnotherUniqueField(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        $fieldRepository = $this->em->getRepository(LeadField::class);
        \assert($fieldRepository instanceof LeadFieldRepository);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');

        $this->saveContact('john@doe.email', '111111111'); // 1
        $this->saveContact('john@doe.email', '111111111'); // 1
        $this->saveContact('john@doe.email', '222222222'); // 2
        $this->saveContact('john@doe.email', '222222222'); // 2
        $this->saveContact('anna@munic.email', '333333333'); // 3
        $this->saveContact('anna@munic.email', '333333333'); // 3
        $this->saveContact('jane@gabriel.email', '4444444444'); // 4
        $this->saveContact('jane.gabriel@gmail.com', '4444444444'); // 5

        $phoneField = $fieldRepository->findOneBy(['alias' => 'phone']);
        \assert($phoneField instanceof LeadField);
        $phoneField->setIsUniqueIdentifer(true);
        $this->em->persist($phoneField);

        $this->em->flush();

        Assert::assertSame(8, $contactRepository->count([]));

        $this->runCommand(DeduplicateCommand::NAME);

        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(5, $contactRepository->count([]));
    }

    public function testDeduplicateCommandWithContactIdsParam(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');

        $contact1 = $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $contact2 = $this->saveContact('jane@doe.email');
        $this->saveContact('jane@doe.email');
        $contact3 = $this->saveContact('anna@munic.email');
        $this->saveContact('anna@munic.email');

        $this->em->flush();

        Assert::assertSame(6, $contactRepository->count([]));

        $this->runCommand(DeduplicateCommand::NAME, ['--contact-ids' => "{$contact1->getId()},{$contact2->getId()},{$contact3->getId()}"]);

        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(3, $contactRepository->count([]));
    }

    public function testDeduplicateCommandWithPrepareCommandParam(): void
    {
        $contact1 = $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $contact2 = $this->saveContact('anna@munic.email');
        $this->saveContact('anna@munic.email');
        $contact3 = $this->saveContact('mattias@doe.email');
        $this->saveContact('mattias@doe.email');
        $contact4 = $this->saveContact('martha@munic.email');
        $this->saveContact('martha@munic.email');

        $this->em->flush();

        $output = $this->runCommand(DeduplicateCommand::NAME, ['--prepare-commands' => true, '--batch' => 2]);

        $expectedOutput = <<<OUTPUT
Deduplicating contacts based on unique identifiers: Email
4 contacts found to deduplicate
bin/console mautic:contacts:deduplicate --contact-ids={$contact1->getId()},{$contact2->getId()}
bin/console mautic:contacts:deduplicate --contact-ids={$contact3->getId()},{$contact4->getId()}
OUTPUT;

        Assert::assertSame($expectedOutput, trim($output));
    }

    private function saveContact(string $email, string $phone = null): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setPhone($phone);
        $contact->setDateIdentified(new \DateTime());

        $this->em->persist($contact);

        return $contact;
    }
}
