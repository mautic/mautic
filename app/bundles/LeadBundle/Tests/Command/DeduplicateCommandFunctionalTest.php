<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeduplicateCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Assert;

final class DeduplicateCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testDeduplicateCommand(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');
        
        $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $this->saveContact('anna@munic.email');
        $this->saveContact('anna@munic.email');
        $this->saveContact('jane@gabriel.email');

        $this->em->flush();

        Assert::assertSame(7, $contactRepository->count([]));

        $this->runCommand(DeduplicateCommand::NAME);

        $contactRepository = $this->em->getRepository(Lead::class);
        \assert($contactRepository instanceof LeadRepository);

        Assert::assertSame(3, $contactRepository->count([]));
    }

    private function saveContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());

        $this->em->persist($contact);

        return $contact;
    }
}
