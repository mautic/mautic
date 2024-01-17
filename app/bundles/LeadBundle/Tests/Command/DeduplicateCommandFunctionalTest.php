<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeduplicateCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;

final class DeduplicateCommandFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        if ('testDeduplicateCommandWithAnotherUniqueFieldAndAnd' === $this->getName()) {
            $this->configParams['contact_unique_identifiers_operator'] = CompositeExpression::TYPE_AND;
        }

        parent::setUp();
    }

    public function testDeduplicateCommandWithUniqueEmail(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);

        $contactDeduper = self::$container->get('mautic.lead.deduper');

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

        $output = $this->testSymfonyCommand(DeduplicateCommand::NAME);

        Assert::assertSame(3, $contactRepository->count([]), $output->getDisplay());
    }

    public function testDeduplicateCommandWithAnotherUniqueFieldAndAnd(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);

        $fieldRepository = $this->em->getRepository(LeadField::class);

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
        $phoneField->setLabel('Cell phone'); // Testing also field with more words.
        $this->em->persist($phoneField);

        $this->em->flush();

        Assert::assertSame(8, $contactRepository->count([]));

        $output = $this->testSymfonyCommand(DeduplicateCommand::NAME);

        Assert::assertSame(5, $contactRepository->count([]), $output->getDisplay());
    }

    public function testDeduplicateCommandWithAnotherUniqueFieldAndOr(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);

        $fieldRepository = $this->em->getRepository(LeadField::class);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');

        $this->saveContact('john@doe.email', '111111111'); // 1
        $this->saveContact('john@doe.email', '111111111'); // 1
        $this->saveContact('john@doe.email', '222222222'); // 1
        $this->saveContact('john@doe.email', '222222222'); // 1
        $this->saveContact('anna@munic.email', '333333333'); // 2
        $this->saveContact('anna@munic.email', '333333333'); // 2
        $this->saveContact('jane@gabriel.email', '4444444444'); // 3
        $this->saveContact('jane.gabriel@gmail.com', '4444444444'); // 3

        $phoneField = $fieldRepository->findOneBy(['alias' => 'phone']);
        \assert($phoneField instanceof LeadField);
        $phoneField->setIsUniqueIdentifer(true);
        $phoneField->setLabel('Cell phone'); // Testing also field with more words.
        $this->em->persist($phoneField);

        $this->em->flush();

        Assert::assertSame(8, $contactRepository->count([]));

        $output = $this->testSymfonyCommand(DeduplicateCommand::NAME);

        Assert::assertSame(3, $contactRepository->count([]), $output->getDisplay());
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
