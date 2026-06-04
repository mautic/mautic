<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;

class CompanyTest extends MauticMysqlTestCase
{
    public function testChangingPropertiesHydratesFieldChanges(): void
    {
        $email    = 'foo@bar.com';
        $company  = new Company();
        $company->addUpdatedField('email', $email);
        $changes = $company->getChanges();

        $this->assertFalse(empty($changes['fields']['email']));

        $this->assertEquals($email, $changes['fields']['email'][1]);
    }

    /**
     * @throws MappingException
     */
    public function testScoreValidationOnCompanyCreate(): void
    {
        $crawler       = $this->client->request(Request::METHOD_GET, '/s/companies/new');
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $form['company[score]']->setValue((string) -3);
        $this->testCompanyData($form);

        $form['company[score]']->setValue((string) 2147483648);
        $this->testCompanyData($form);
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testMinScoreValidationOnCompanyEdit(): void
    {
        $company = new Company();
        $company->setScore(-1);

        $this->em->persist($company);
        $this->em->flush();

        $companyId = $company->getId();

        $crawler       = $this->client->request(Request::METHOD_GET, '/s/companies/edit/'.$companyId);
        $buttonCrawler = $crawler->selectButton('Save & Close');
        $form          = $buttonCrawler->form();
        $form['company[score]']->setValue((string) -3);
        $this->testCompanyData($form);

        $form['company[score]']->setValue((string) 2147483648);
        $this->testCompanyData($form);
    }

    /**
     * @throws MappingException
     */
    private function testCompanyData(Form $form): void
    {
        $errorMessage = 'This value should be between 0 and 2147483647.';

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->em->clear();

        $response = $this->client->getResponse()->getContent();
        $this->assertStringContainsString($errorMessage, (string) $response);
    }
}
