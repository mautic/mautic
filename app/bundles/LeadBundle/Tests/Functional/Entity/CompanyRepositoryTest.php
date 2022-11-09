<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\HttpFoundation\Request;

final class CompanyRepositoryTest extends MauticMysqlTestCase
{
    public function testEmailPreviewWithCompanyTokens(): void
    {
        $suffix   = random_int(10, 100);
        $companyA = $this->createCompany('ABC Co.'.$suffix, 'First Street'.$suffix);
        $contactA = $this->createContact('John'.$suffix, 'JohnDoe'.$suffix.'@email.com', $companyA);
        $companyB = $this->createCompany('XYZ Co.'.$suffix, 'Second Street'.$suffix);
        $this->editContact($contactA, $companyB, $companyA);
        $emailId = $this->createEmail('EmailName'.$suffix, 'Subject'.$suffix, 'template');
        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/email/preview/'.$emailId.'/real?contactId='.$contactA->getId(),
        );
        self::assertStringContainsString('JohnDoe'.$suffix.'@email.com', $crawler->text());
        self::assertStringContainsString('XYZ Co.'.$suffix, $crawler->text());
        self::assertStringContainsString('Second Street'.$suffix, $crawler->text());
    }

    private function createCompany(string $name, string $address1 = ''): Company
    {
        /** @var CompanyModel $model */
        $model   = self::$container->get('mautic.lead.model.company');
        $company = new Company();
        $company
            ->setIsPublished(true)
            ->setName($name)
            ->setAddress1($address1);
        $model->saveEntity($company);

        return $company;
    }

    private function createContact(string $firstName, string $email, Company $company): Lead
    {
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/new');
        $formCrawler = $crawler->filter('form[name=lead]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues(
            [
                'lead[firstname]' => $firstName,
                'lead[email]'     => $email,
                'lead[companies]' => [$company->getId()],
            ]
        );
        $this->client->submit($form);

        return $this->em->getRepository(Lead::class)->findOneBy(
            [
                'firstname' => $firstName,
                'email'     => $email,
            ]
        );
    }

    private function editContact(Lead $contact, Company $primaryCompany, Company $secondaryCompany): void
    {
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$contact->getId());
        $formCrawler = $crawler->filter('form[name=lead]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues(
            [
                'lead[companies]' => [$primaryCompany->getId(), $secondaryCompany->getId()],
            ]
        );
        $this->client->submit($form);
    }

    private function createEmail(string $name, string $subject, string $emailType, int $segmentId = null): int
    {
        $payload = [
            'name'       => $name,
            'subject'    => $subject,
            'emailType'  => $emailType,
            'customHtml' => '{contactfield=email} {contactfield=companyname} {contactfield=companyaddress1}',
        ];

        if ('list' === $emailType) {
            $payload['lists'] = [$segmentId];
        }

        $this->client->request('POST', '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        return $response['email']['id'];
    }
}
