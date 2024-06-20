<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeduplicateIdsCommand;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class DeduplicateIdsCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testDeduplicateCommandWithContactIdsParam(): void
    {
        $contactRepository = $this->em->getRepository(Lead::class);

        Assert::assertSame(0, $contactRepository->count([]), 'Some contacts were forgotten to remove from other tests');

        $contact1 = $this->saveContact('john@doe.email');
        $this->saveContact('john@doe.email');
        $contact2 = $this->saveContact('jane@doe.email');
        $this->saveContact('jane@doe.email');
        $contact3 = $this->saveContact('anna@munic.email');
        $this->saveContact('anna@munic.email');

        $this->em->flush();

        Assert::assertSame(6, $contactRepository->count([]));

        $this->testSymfonyCommand(DeduplicateIdsCommand::NAME, ['--contact-ids' => "{$contact1->getId()},{$contact2->getId()},{$contact3->getId()}"]);

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
