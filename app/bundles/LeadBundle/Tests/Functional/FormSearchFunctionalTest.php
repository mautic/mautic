<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('non-parallel')]
final class FormSearchFunctionalTest extends MauticMysqlTestCase
{
    public function testFormSearchReturnsContactsAssociatedWithFormSubmissions(): void
    {
        $submittedContact      = $this->createContact('submitted-form-contact@example.test');
        $otherSubmittedContact = $this->createContact('other-form-contact@example.test');
        $notSubmittedContact   = $this->createContact('no-form-contact@example.test');
        $form                  = $this->createForm('Newsletter form', 'newsletter-form');
        $otherForm             = $this->createForm('Demo form', 'demo-form');

        $this->createSubmission($form, $submittedContact);
        $this->createSubmission($otherForm, $otherSubmittedContact);
        $this->em->flush();
        $this->em->clear();

        $leadRepository = $this->em->getRepository(Lead::class);
        $this->assertInstanceOf(LeadRepository::class, $leadRepository);
        $this->assertContains('mautic.lead.lead.searchcommand.form', $leadRepository->getSearchCommands());

        $this->assertSearchResult('form%3Anewsletter-form', [$submittedContact], [$otherSubmittedContact, $notSubmittedContact]);
        $this->assertSearchResult('!form%3Anewsletter-form', [$otherSubmittedContact, $notSubmittedContact], [$submittedContact]);
        $this->assertSearchResult('form%3Amissing-form', [], [$submittedContact, $otherSubmittedContact, $notSubmittedContact]);
        $this->assertSearchResult('form%3A', [], [$submittedContact, $otherSubmittedContact, $notSubmittedContact]);
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);

        return $contact;
    }

    private function createForm(string $name, string $alias): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias($alias);
        $form->setIsPublished(true);
        $this->em->persist($form);

        return $form;
    }

    private function createSubmission(Form $form, Lead $contact): void
    {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setLead($contact);
        $submission->setDateSubmitted(new \DateTime());
        $submission->setReferer('https://example.test');
        $this->em->persist($submission);
    }

    /**
     * @param Lead[] $expectedContacts
     * @param Lead[] $notExpectedContacts
     */
    private function assertSearchResult(string $search, array $expectedContacts, array $notExpectedContacts): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search='.$search);
        self::assertResponseIsSuccessful();
        $responseText = $crawler->text();

        foreach ($expectedContacts as $expectedContact) {
            $this->assertStringContainsString($expectedContact->getEmail(), $responseText);
        }

        foreach ($notExpectedContacts as $notExpectedContact) {
            $this->assertStringNotContainsString($notExpectedContact->getEmail(), $responseText);
        }
    }
}
