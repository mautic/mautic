<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SubmissionModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testSaveSubmissionChangeCompanyField(): void
    {
        [$formId, $formAlias] = $this->createFormWithCompanies();

        $this->submitFormWithCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe', 'Acquia', 'Keplerova');

        // Check the address.
        $companyRepository = $this->em->getRepository(Company::class);
        $companiesOriginal = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(1, $companiesOriginal);

        // Create contact with the same company but different address.
        $this->submitFormWithCompanies($formId, $formAlias, 'test2@acquia.cz', 'Luk', 'Syk', 'Acquia', 'Krejpskeho');

        // Check that the address is changed.
        $companiesOld = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(0, $companiesOld);
        $companiesNew = $companyRepository->findBy(['address1' => 'Krejpskeho']);
        Assert::assertCount(1, $companiesNew);
    }

    public function testSaveSubmissionChangeContactField(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe Smith');

        // Check the contact.
        $contactRepository = $this->em->getRepository(Lead::class);
        $contactsOriginal  = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(1, $contactsOriginal);

        // Create contact with the same email but different lastname.
        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Sykora');

        // Check that the address is changed.
        $contactsOld = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(0, $contactsOld);
        $contactsNew = $contactRepository->findBy(['lastname' => 'Sykora']);
        Assert::assertCount(1, $contactsNew);
    }

    public function testExistingContactWinsMergeWhenTrackedAnonymousContactSubmitsMatchingForm(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        $existingContact = new Lead();
        $existingContact->setEmail('existing.winner@example.com')
            ->setFirstname('Existing')
            ->setLastname('Contact');
        $this->em->persist($existingContact);

        $anonymousContact = new Lead();
        $anonymousContact->setFirstname('Anonymous');
        $this->em->persist($anonymousContact);
        $this->em->flush();

        $existingContactId  = (int) $existingContact->getId();
        $anonymousContactId = (int) $anonymousContact->getId();

        $this->logoutUser();

        $contactTracker = static::getContainer()->get('mautic.tracker.contact');
        Assert::assertInstanceOf(ContactTracker::class, $contactTracker);
        $contactTracker->setTrackedContact($anonymousContact);

        $this->submitFormWithoutCompanies($formId, $formAlias, 'existing.winner@example.com', 'Updated', 'Winner');

        $this->em->clear();

        Assert::assertSame($existingContactId, $this->getSubmissionLeadId($formId));
        Assert::assertSame(1, $this->countLeadRowsById($existingContactId));
        Assert::assertSame(0, $this->countLeadRowsById($anonymousContactId));

        $contact = $this->em->getRepository(Lead::class)->find($existingContactId);
        Assert::assertInstanceOf(Lead::class, $contact);
        Assert::assertSame('existing.winner@example.com', $contact->getEmail());
        Assert::assertSame('Updated', $contact->getFirstname());
        Assert::assertSame('Winner', $contact->getLastname());
    }

    /**
     * @return mixed[]
     */
    private function createFormWithCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
                [
                    'label'        => 'Company',
                    'type'         => 'text',
                    'alias'        => 'companyname',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyname',
                ],
                [
                    'label'        => 'Company Address',
                    'type'         => 'text',
                    'alias'        => 'companyaddress1',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyaddress1',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @return mixed[]
     */
    private function createFormWithoutCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @param mixed[] $payload
     *
     * @return array{int,string}
     */
    private function createForm(array $payload): array
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $response  = json_decode($this->client->getResponse()->getContent(), true);
        $formId    = $response['form']['id'];
        $formAlias = $response['form']['alias'];
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return [$formId, $formAlias];
    }

    private function submitFormWithCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname, string $company, string $companyAddress): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
            'mauticform[companyname]'     => $company,
            'mauticform[companyaddress1]' => $companyAddress,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    private function submitFormWithoutCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    /**
     * @param array<string,string> $values
     */
    private function submitForm(int $formId, string $formAlias, array $values): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues($values);
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
    }

    private function getSubmissionLeadId(int $formId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT lead_id FROM '.MAUTIC_TABLE_PREFIX.'form_submissions WHERE form_id = ?',
            [$formId]
        );
    }

    private function countLeadRowsById(int $leadId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'leads WHERE id = ?',
            [$leadId]
        );
    }
}
