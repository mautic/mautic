<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form as FormEntity;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class CampaignActionAnonymizeUserDataSubscriberFormFunctionalTest extends MauticMysqlTestCase
{
    public const EVENT_LEAD_TYPE = 'lead.action_anonymizeuserdata';

    public const URI_EVENT_NEW = '/s/campaigns/events/new?type='.self::EVENT_LEAD_TYPE.'&eventType=action&campaignId=mautic_85edec486b8a978db4a63f22ef588c74efd85d9e';

    public const FIELD_TYPE_ALLOWED = [
        'text',
        'email',
    ];

    public const TEST_TABLE_NAME = 'form_results_est';

    public function setUp(): void
    {
        $this->useCleanupRollback = false;
        parent::setUp();
    }

    public function testCheckActionFormIsWorking(): void
    {
        $this->client->request('GET', self::URI_EVENT_NEW, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        Assert::assertStringContainsString('Anonymize User Data', $response->getContent());
        Assert::assertStringContainsString('Anonymize User Data in these fields', $response->getContent());
        Assert::assertStringContainsString('Zip Code', $response->getContent());
        Assert::assertStringContainsString('Address Line 1', $response->getContent());
        Assert::assertStringContainsString('Instagram', $response->getContent());
        Assert::assertStringContainsString('Pseudonymization will turn the personal data into a one', $response->getContent());
    }

    public function testCheckIfCharacterLessThan64IsListedInAnonymize(): void
    {
        $newField = $this->newField('new_field_less_than_64', 'New Field Less Than 64', 20);
        $this->client->request('GET', self::URI_EVENT_NEW, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        Assert::assertStringContainsString($newField->getLabel(), $response->getContent());
        $responseContent = $response->getContent();
        preg_match_all('/Address Line 1/', $responseContent, $matches);
        $this->assertCount(2, $matches[0], $response->getContent());
        preg_match_all('/'.$newField->getLabel().'/', $responseContent, $matches);
        $this->assertCount(2, $matches[0], $response->getContent());
    }

    public function testAnonymizeUserDataAction(): void
    {
        $nameEvent           = 'Anonymize User Data Test';
        $responseCreateEvent = $this->createEvent($nameEvent, ['11', '2'], ['3', '5']);
        $responseData        = json_decode($responseCreateEvent->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($responseCreateEvent->getContent(), true), true));
        Assert::assertStringContainsString($nameEvent, $responseCreateEvent->getContent());

        Assert::assertContains(11, $responseData['event']['properties']['fieldsToAnonymize']);
        Assert::assertContains(2, $responseData['event']['properties']['fieldsToAnonymize']);
        Assert::assertContains(5, $responseData['event']['properties']['fieldsToDelete']);

        $eventId    = $responseData['event']['id'];
        $campaignId = $responseData['event']['properties']['campaignId'];

        // GET EDIT FORM
        $uri = "/s/campaigns/events/edit/{$eventId}?campaignId={$campaignId}&anchor=leadsource&anchorEventType=source";
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk(), $response->getContent());

        // FILL EDIT FORM
        $responseData                  = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $crawler                       = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form                          = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $values                        = $form->getValues();
        $nameEvent                     = 'Anonymize User Data Updated Test';
        $values                        = array_merge($values, $this->getDefaultValuesForm(['2', '5'], ['4'], $nameEvent));
        $values['campaignevent[name]'] = 'Anonymize User Data Updated Test';
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertTrue(
            $responseData['success'],
            print_r($responseData, true)
        );
        Assert::assertStringContainsString($nameEvent, $response->getContent());
        Assert::assertContains(4, $responseData['event']['properties']['fieldsToDelete']);
        Assert::assertContains(2, $responseData['event']['properties']['fieldsToAnonymize']);
        Assert::assertContains(5, $responseData['event']['properties']['fieldsToAnonymize']);
    }

    /**
     * @param array<int,string> $fieldsToAnonymize
     * @param array<int,string> $fieldsToDelete
     */
    private function createEvent(string $nameEvent, array $fieldsToAnonymize, array $fieldsToDelete): \Symfony\Component\HttpFoundation\Response
    {
        $this->client->request('GET', self::URI_EVENT_NEW, [], [], $this->createAjaxHeaders());
        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $values       = $form->getValues();
        $values       = array_merge($values, $this->getDefaultValuesForm($fieldsToAnonymize, $fieldsToDelete, $nameEvent));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());

        return $response;
    }

    private function baseRequest(): Form
    {
        $this->client->request('GET', self::URI_EVENT_NEW, [], [], $this->createAjaxHeaders());
        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $crawler = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());

        return $crawler->filterXPath('//form[@name="campaignevent"]')->form();
    }

    public function testAnonymizeUserDataActionWithInvalidFields(): void
    {
        $form   = $this->baseRequest();
        $values = $form->getValues();
        // Fields: First Name, Last Name and Address Line 1.
        $fieldsToAnonymize = ['2', '3', '11'];
        // Fields: First Name, Position, Address Line 2 and Last Name.
        $fieldsToDelete = ['2', '5', '12', '3'];
        $nameEvent      = 'Event Anonymize Data With Invalid Fields';
        $values         = array_merge($values, $this->getDefaultValuesForm($fieldsToAnonymize, $fieldsToDelete, $nameEvent));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(0, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString($nameEvent, $responseData['newContent']);
        Assert::assertStringContainsString('The field(s) can&#039;t be both deleted and anonymized: ', $responseData['newContent']);
        Assert::assertStringContainsString('<li>First Name</li>', $responseData['newContent']);
        Assert::assertStringContainsString('<li>Last Name</li>', $responseData['newContent']);
    }

    public function testAnonymizeUserDataActionInvalidWithEmptyFields(): void
    {
        $form      = $this->baseRequest();
        $values    = $form->getValues();
        $nameEvent = 'Anonymize Invalid User Data Test';
        $values    = array_merge($values, $this->getDefaultValuesForm([], [], $nameEvent));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(0, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString($nameEvent, $responseData['newContent']);
        Assert::assertStringContainsString('The field(s) can&#039;t be empty', $responseData['newContent']);
    }

    public function testIfFieldsWithUniqueIdentifierAreNotBring(): void
    {
        $uri = self::URI_EVENT_NEW;
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        preg_match_all('/Instagram/', $response->getContent(), $matches);
        Assert::assertCount(2, $matches[0]);
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $entity     = $fieldModel->getRepository()->findOneBy(['alias' => 'instagram']);
        assert($entity instanceof LeadField);
        $entity->setIsUniqueIdentifer(true);
        $fieldModel->saveEntity($entity);
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        preg_match_all('/Instagram/', $response->getContent(), $matches);
        Assert::assertCount(1, $matches[0]);
    }

    public function testIfEmailFieldIsComingOneTime(): void
    {
        $uri = self::URI_EVENT_NEW;
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        Assert::assertStringContainsString('Email', $response->getContent());
    }

    public function testAllFieldsToAnonymizeData(): void
    {
        $newField         = $this->newField('new_field2', 'New All Fields', 32);
        $nameEvent        = 'Anonymize User Data Test';
        $fieldModel       = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $getFieldChoices  = $this->getFieldChoices(false, true);
        $response         = $this->createEvent($nameEvent, array_values($getFieldChoices), []);
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString($nameEvent, $response->getContent());
        Assert::assertContains($newField->getId(), $responseData['event']['properties']['fieldsToAnonymize']);
    }

    public function testPseudonymizeData(): void
    {
        $auditLogModel = static::getContainer()->get('mautic.core.model.auditlog');
        assert($auditLogModel instanceof \Mautic\CoreBundle\Model\AuditLogModel);
        $checkAuditLog = $auditLogModel->getRepository()->findAll();
        Assert::assertEmpty($checkAuditLog);
        // Set email field as unique to duplicate the email
        $this->setEmailUnique();
        $email1   = 'foo@baa.com';
        $email2   = 'jhondoe@test.com';
        $lead1    = $this->createLead('Test1', 'Lastname1', $email1);
        $lead2    = $this->createLead('Test2', 'Lastname2', $email1);
        $lead3    = $this->createLead('Test3', 'Lastname2', $email2);
        $lead4    = $this->createLead('Test4', 'Lastname3', $email2);
        $company1 = $this->createCompany('Company 1', 'emailCompany@test.com');
        $company2 = $this->createCompany('Company 2', 'company2@email.com');

        $lead1->setCompany($company1);
        $lead1->setPrimaryCompany($company1);
        $lead2->setCompany($company1);
        $lead2->setPrimaryCompany($company1);
        $lead3->setCompany($company2);
        $lead3->setPrimaryCompany($company2);
        $lead4->setCompany($company2);
        $lead4->setPrimaryCompany($company2);
        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->persist($lead3);
        $this->em->persist($lead4);
        $this->em->flush();

        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        assert($leadModel instanceof LeadModel);
        $leadModel->getRepository()->saveEntity($lead1);
        $leadModel->getRepository()->saveEntity($lead2);
        $leadModel->getRepository()->saveEntity($lead3);
        $leadModel->getRepository()->saveEntity($lead4);

        $this->addCompanyOnLead($lead1, $company1);
        $this->addCompanyOnLead($lead2, $company1);
        $this->addCompanyOnLead($lead3, $company2);
        $this->addCompanyOnLead($lead4, $company2);

        $resultFormWithSubmissions = $this->createFormSubmissions([$lead1, $lead2, $lead3], 'Test Form 11', true, [$email1, $email2]);
        $resultForms               = $resultFormWithSubmissions['resultForms'];
        //
        //        $emailEntity = new Email();
        //        $emailEntity->setSubject('Test Email');
        //        $emailEntity->setFromName('Test');
        //        $emailEntity->setName('Test Email');
        //        $this->em->persist($emailEntity);
        //        $this->em->flush();
        //
        $emailStat1       = $resultFormWithSubmissions['emailStat1'];
        $emailStat2       = $resultFormWithSubmissions['emailStat2'];
        $emailStat3       = $resultFormWithSubmissions['emailStat3'];
        //        $getFieldChoices  = $this->getFieldChoices(false);
        $list   = $this->createLeadList('Test List');

        $this->addLeadToList([$lead1, $lead2, $lead3], $list);
        $campaign    = $this->createCampaign($list);
        // Fields Anonymize: First Name, Last Name, Email, Company Address 1
        // Fields to deleted: Primary Company, Position, Address Line 1, Company Address 2
        $event = $this->createCampaignEvent($campaign, 'Event Test', ['2', '3', '6', '29'], ['4', '5', '11', '30'], true);

        // add event
        $campaign->addEvent('lead.action_anonymizeuserdata', $event);
        $this->em->flush();
        $this->em->clear();

        $checkAuditLogBeginLead1 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $lead1,
                'object'   => 'lead',
                'bundle'   => 'lead',
            ]
        );

        $this->assertNotEmpty($checkAuditLogBeginLead1);
        $this->assertCount(2, $checkAuditLogBeginLead1);

        $checkAuditLogBeginLead2 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $lead2,
                'object'   => 'lead',
                'bundle'   => 'lead',
            ]
        );

        $this->assertNotEmpty($checkAuditLogBeginLead2);
        $this->assertCount(2, $checkAuditLogBeginLead2);

        $checkAuditLogBeginCompany1 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $company1,
                'object'   => 'company',
                'bundle'   => 'lead',
            ]
        );

        $this->assertNotEmpty($checkAuditLogBeginCompany1);
        $this->assertCount(1, $checkAuditLogBeginCompany1);

        $resultOldForm1Table = $this->getResultOfNewTable($resultForms['forms'][0]);
        $this->assertNotEmpty($resultOldForm1Table);
        $this->assertCount(3, $resultOldForm1Table);
        $resultOldForm2Table = $this->getResultOfNewTable($resultForms['forms'][1]);
        $this->assertEmpty($resultOldForm2Table);

        $this->runCampaignUpdateTrigger($campaign->getId());

        $auditLogModel = static::getContainer()->get('mautic.core.model.auditlog');
        assert($auditLogModel instanceof \Mautic\CoreBundle\Model\AuditLogModel);
        $checkAuditLog1 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $lead1,
                'object'   => 'lead',
                'bundle'   => 'lead',
            ]
        );

        $this->assertEmpty($checkAuditLog1);

        $checkAuditLog2 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $lead2,
                'object'   => 'lead',
                'bundle'   => 'lead',
            ]
        );

        $this->assertEmpty($checkAuditLog2);

        $checkAuditLog3 = $auditLogModel->getRepository()->findBy(
            [
                'objectId' => $company1,
                'object'   => 'company',
                'bundle'   => 'lead',
            ]
        );

        $this->assertEmpty($checkAuditLog3);

        $newLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $newLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $newLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $resultForm1Table = $this->getResultOfNewTable($resultForms['forms'][0]);
        $resultForm2Table = $this->getResultOfNewTable($resultForms['forms'][1]);

        $this->assertNotEmpty($resultForm1Table);
        $this->assertCount(3, $resultForm1Table);
        $this->assertSame($resultForm1Table[1]['field_lastname'], $resultForm1Table[2]['field_lastname']);
        $this->assertEmpty($resultForm2Table);

        $this->assertSame($newLead1->getEmail(), $newLead2->getEmail());
        $this->assertStringContainsString('@pseudo.nym', $newLead1->getEmail());
        $this->assertNotSame($newLead1->getEmail(), $lead1->getEmail());
        $this->assertNotSame($newLead1->getFirstname(), $lead1->getFirstname());
        $this->assertNotSame($newLead3->getLastname(), $lead3->getLastname());
        $this->assertNotEmpty($lead1->getAddress1());
        $this->assertEmpty($newLead1->getAddress1());

        $newEmailStat1 = $this->em->getRepository(Stat::class)->find($emailStat1->getId());
        $newEmailStat2 = $this->em->getRepository(Stat::class)->find($emailStat2->getId());
        $newEmailStat3 = $this->em->getRepository(Stat::class)->find($emailStat3->getId());

        $this->assertSame($newEmailStat1->getEmailAddress(), $newEmailStat2->getEmailAddress());
        $this->assertStringContainsString('@pseudo.nym', $newEmailStat1->getEmailAddress());
        $this->assertSame($newLead1->getEmail(), $newEmailStat1->getEmailAddress());
        $this->assertNotSame($newEmailStat1->getEmailAddress(), $email1);

        $this->assertNotSame($newEmailStat3->getEmailAddress(), $newEmailStat2->getEmailAddress());

        $companyLead1       = $this->em->getRepository(Company::class)->find($company1->getId());
        $companyEntity1     = $this->em->getRepository(Company::class)->find($company1->getId());
        $companyLead2       = $this->em->getRepository(Company::class)->find($company2->getId());

        $this->assertNotNull($companyLead1->getAddress1());
        $this->assertNotNull($company1->getAddress1());
        $this->assertNotSame($companyLead1->getAddress1(), $company1->getAddress1());
        $this->assertNotNull($company1->getAddress2());
        $this->assertNull($companyLead1->getAddress2());
    }

    public function testPseudonymizeDataWithPseudonymizeInt1(): void
    {
        $this->runCampaignToValidateError(1);
    }

    public function testPseudonymizeDataWithPseudonymize0(): void
    {
        $this->runCampaignToValidateError(0);
    }

    public function testPseudonymizeDataWithPseudonymizeTrue(): void
    {
        $this->runCampaignToValidateError(true);
    }

    public function testPseudonymizeDataWithPseudonymizeFalse(): void
    {
        $this->runCampaignToValidateError(false);
    }

    /**
     * @param array<int,Lead> $leads
     * @param array<int,string> $emails
     *
     * @return array<string,mixed>
     */
    private function createFormSubmissions(array $leads, string $nameSubmission, bool $sameName, array $emails): array
    {
        $resultForms = $this->createFormWithSubmissions($leads, $nameSubmission, $sameName);

        $emailEntity = new Email();
        $emailEntity->setSubject('Test Email');
        $emailEntity->setFromName('Test');
        $emailEntity->setName('Test Email');
        $this->em->persist($emailEntity);
        $this->em->flush();

        $emailStat1       = $this->addEmailStat($leads[0], $emailEntity, $emails[0]);
        $emailStat2       = $this->addEmailStat($leads[1], $emailEntity, $emails[0]);
        $emailStat3       = $this->addEmailStat($leads[2], $emailEntity, $emails[1]);
        $getFieldChoices  = $this->getFieldChoices(false);

        return [
            'resultForms'     => $resultForms,
            'emailStat1'      => $emailStat1,
            'emailStat2'      => $emailStat2,
            'emailStat3'      => $emailStat3,
            'getFieldChoices' => $getFieldChoices,
        ];
    }

    /**
     * @param int|bool $psendonymizeEvent
     */
    private function runCampaignToValidateError($psendonymizeEvent): void
    {
        $auditLogModel = static::getContainer()->get('mautic.core.model.auditlog');
        assert($auditLogModel instanceof \Mautic\CoreBundle\Model\AuditLogModel);
        $checkAuditLog = $auditLogModel->getRepository()->findAll();
        Assert::assertEmpty($checkAuditLog);
        // Set email field as unique to duplicate the email
        $this->setEmailUnique();
        $email1   = 'foo@baa.com';
        $lead1    = $this->createLead('Test1', 'Lastname1', $email1);
        $company1 = $this->createCompany('Company 1', 'company2@email.com');

        $lead1->setCompany($company1);
        $lead1->setPrimaryCompany($company1);
        $this->em->persist($lead1);
        $this->em->flush();
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        assert($leadModel instanceof LeadModel);
        $leadModel->getRepository()->saveEntity($lead1);

        $this->addCompanyOnLead($lead1, $company1);

        $resultForms      = $this->createFormSubmissions([$lead1, $lead1, $lead1], 'Test Form 11', true, [$email1, $email1]);
        $emailStat1       = $resultForms['emailStat1'];
        $list             = $this->createLeadList('Test List');

        $this->addLeadToList([$lead1], $list);
        $campaign    = $this->createCampaign($list);

        // Fields Anonymize: First Name, Last Name, Email, Company Address 1
        // Fields to deleted: Primary Company, Position, Address Line 1, Company Address 2
        $event = $this->createCampaignEvent($campaign, 'Event Test Error', ['2', '3', '6', '29'], ['4', '5', '11', '30'], $psendonymizeEvent);

        // add event
        $campaign->addEvent('lead.action_anonymizeuserdata', $event);
        $this->em->flush();
        $this->em->clear();

        $resultCommands            = $this->runCampaignUpdateTrigger($campaign->getId());
        $resultRunCommandCampaign2 = $resultCommands['resultRunCommandCampaign2'];
        self::assertStringContainsString('1 total event was executed', $resultRunCommandCampaign2->getDisplay());
    }

    private function createCompany(string $name = 'Company', string $email='company@foobaa.com'): Company
    {
        $leadFields = $this->em->getRepository(LeadField::class)->findOneBy(['alias' => 'companyaddress1']);

        $company = new Company();
        $company->setName($name);
        $company->setDescription('Company Description');
        $company->setIndustry('Industry');
        $company->setWebsite('www.company.com');
        $company->setEmail($email);
        $company->setPhone('1234567890');
        $company->setAddress1('Company Address 1');
        $company->setAddress2('Company Address 2');
        $company->setCity('Company City');

        $companyModel = static::getContainer()->get('mautic.lead.model.company');
        assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);
        $companyModel->setFieldValues($company, []);
        $companyModel->setFieldValues($company, [
            'companyaddress1' => [
                'value' => 'Company Address 1',
                'label' => 'Company Address 1',
            ],
            'companyaddress2' => [
                'value' => 'Company Address 2',
                'label' => 'Company Address 2',
            ],
            'companydescription111' => [
                'value' => 'Company Description',
                'label' => 'Company Description',
            ],
        ]);

        $companyModel = static::getContainer()->get('mautic.lead.model.company');
        assert($companyModel instanceof \Mautic\LeadBundle\Model\CompanyModel);
        $companyModel->getRepository()->saveEntity($company);

        $auditLogModel = static::getContainer()->get('mautic.core.model.auditlog');
        assert($auditLogModel instanceof \Mautic\CoreBundle\Model\AuditLogModel);
        $auditLogModel->writeToLog(
            [
                'bundle'    => 'lead',
                'object'    => 'company',
                'objectId'  => $company->getId(),
                'action'    => 'create',
                'details'   => [],
            ]
        );

        return $company;
    }

    /**
     * @return array<string, Lead|Company|CompanyLead>
     */
    private function addCompanyOnLead(Lead $lead, Company $company, bool $primaryCompany = true): array
    {
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setPrimary($primaryCompany);
        $companyLead->setDateAdded(new \DateTime());
        $lead->setPrimaryCompany($company);
        $lead->setCompany($company);
        $this->em->persist($companyLead);
        $this->em->persist($lead);
        $this->em->persist($company);
        $this->em->flush();

        return [
            'lead'        => $lead,
            'company'     => $company,
            'companyLead' => $companyLead,
        ];
    }

    public function testAnonymizeData(): void
    {
        // Set email field as unique to duplicate the email
        // Set email field as unique to duplicate the email
        $this->setEmailUnique();
        $email1 = 'foo@baa.com';
        $email2 = 'jhondoe@test.com';
        $lead1  = $this->createLead('Test1', 'Lastname1', $email1);
        $lead2  = $this->createLead('Test2', 'Lastname2', $email1);
        $lead3  = $this->createLead('Test3', 'Lastname3', $email2);
        $list   = $this->createLeadList('Test List');
        $this->addLeadToList([$lead1, $lead2, $lead3], $list);
        $resultForms = $this->createFormWithSubmissions([$lead1, $lead2, $lead3]);
        $campaign    = $this->createCampaign($list);

        $emailEntity = new Email();
        $emailEntity->setSubject('Test Email');
        $emailEntity->setFromName('Test');
        $emailEntity->setName('Test Email');
        $this->em->persist($emailEntity);
        $this->em->flush();
        $emailStat1       = $this->addEmailStat($lead1, $emailEntity, $email1);
        $emailStat2       = $this->addEmailStat($lead2, $emailEntity, $email1);
        $emailStat3       = $this->addEmailStat($lead3, $emailEntity, $email2);
        $getFieldChoices  = $this->getFieldChoices(false);

        // Fields Anonymize: First Name, Last Name
        // Fields to deleted: Primary Company, Position, Address Line 1
        $event = $this->createCampaignEvent($campaign, 'Event Test Anonymize', ['2', '3', '6'], ['4', '5', '11'], false);

        // add event
        $campaign->addEvent('lead.action_anonymizeuserdata', $event);
        $this->em->flush();
        $this->em->clear();

        $resultOldForm1Table = $this->getResultOfNewTable($resultForms['forms'][0]);
        $this->assertNotEmpty($resultOldForm1Table);
        $this->assertCount(3, $resultOldForm1Table);
        $resultOldForm2Table = $this->getResultOfNewTable($resultForms['forms'][1]);
        $this->assertEmpty($resultOldForm2Table);

        $this->runCampaignUpdateTrigger($campaign->getId());

        $newLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $newLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $newLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $resultForm1Table = $this->getResultOfNewTable($resultForms['forms'][0]);
        $resultForm2Table = $this->getResultOfNewTable($resultForms['forms'][1]);

        $resultForm1Table = $this->getResultOfNewTable($resultForms['forms'][0]);
        $resultForm2Table = $this->getResultOfNewTable($resultForms['forms'][1]);

        $this->assertNotEmpty($resultForm1Table);
        $this->assertCount(3, $resultForm1Table);
        $this->assertNotSame($resultForm1Table[1]['field_lastname'], $resultForm1Table[2]['field_lastname']);
        $this->assertEmpty($resultForm2Table);

        $this->assertNotSame($newLead1->getEmail(), $newLead2->getEmail());
        $this->assertStringContainsString('@ano.nym', $newLead1->getEmail());
        $this->assertNotSame($newLead1->getEmail(), $lead1->getEmail());
        $this->assertNotSame($newLead1->getFirstname(), $lead1->getFirstname());
        $this->assertNotSame($newLead3->getLastname(), $lead3->getLastname());
        $this->assertNotEmpty($lead1->getAddress1());
        $this->assertEmpty($newLead1->getAddress1());

        $newEmailStat1 = $this->em->getRepository(Stat::class)->find($emailStat1->getId());
        $newEmailStat2 = $this->em->getRepository(Stat::class)->find($emailStat2->getId());
        $newEmailStat3 = $this->em->getRepository(Stat::class)->find($emailStat3->getId());

        $this->assertNotSame($newEmailStat1->getEmailAddress(), $newEmailStat2->getEmailAddress());
        $this->assertNotSame($newEmailStat1->getEmailAddress(), $email1);
        $this->assertNotSame($newEmailStat3->getEmailAddress(), $newEmailStat2->getEmailAddress());

        $this->assertNotSame($newEmailStat1->getEmailAddress(), $emailStat1->getEmailAddress());
    }

    /**
     * @param array <Lead> $leads
     *
     * @return array <string, array<mixed>>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createFormWithSubmissions(array $leads, string $name ='Test Form', bool $sameName = false): array
    {
        $formPayload = [
            'name'               => 'Test Base Form',
            'formType'           => 'standalone',
            'postActionProperty' => 'casa',
            'fields'             => [
                [
                    'label'      => 'Email',
                    'alias'      => 'field_email',
                    'type'       => 'email',
                    'leadField'  => 'email',
                    'isRequired' => false,
                ],
                [
                    'label'      => 'Name',
                    'alias'      => 'field_lastname',
                    'type'       => 'text',
                    'leadField'  => 'lastname',
                    'isRequired' => false,
                ],
                [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse  = $this->client->getResponse();
        $response1       = json_decode($clientResponse->getContent(), true);

        $formPayload['name']               = $name;
        $formPayload['postActionProperty'] = 'Thank you casa';
        $this->client->request('POST', '/api/forms/new', $formPayload);

        $clientResponse  = $this->client->getResponse();
        $response2       = json_decode($clientResponse->getContent(), true);
        $submissionModel = static::getContainer()->get('mautic.form.model.submission');
        \assert($submissionModel instanceof SubmissionModel);
        foreach ($leads as $key => $lead) {
            assert($lead instanceof Lead);
            $name       = $sameName ? $lead->getLastname() : $lead->getLastname().'_'.$key;

            $submission = $this->addSubmission($response2['form']['id'], $key.$lead->getEmail(), $name);

            $submission->setLead($lead);

            $this->em->persist($submission);

            $this->em->flush();
        }

        $formModel = static::getContainer()->get('mautic.form.model.form');
        assert($formModel instanceof FormModel);
        $formEntity1      = $formModel->getRepository()->find($response2['form']['id']);
        $submissionsForm1 = $submissionModel->getRepository()->findBy(['form' => $formEntity1]);
        $this->assertCount(3, $submissionsForm1);
        foreach ($submissionsForm1 as $submission) {
            $this->assertContains($submission->getLead()->getEmail(), [$leads[0]->getEmail(), $leads[1]->getEmail(), $leads[2]->getEmail()]);
        }

        $formEntity2      = $formModel->getRepository()->find($response1['form']['id']);
        $submissionsForm2 = $submissionModel->getRepository()->findBy(['form' => $formEntity2]);
        $this->assertCount(0, $submissionsForm2);

        return [
            'forms'       => [$formEntity1, $formEntity2],
            'submissions' => [
                'form1' => $submissionsForm1,
                'form2' => $submissionsForm2,
            ],
        ];
    }

    private function addSubmission(int $id, string $email, string $lastname): ?Submission
    {
        $crawler = $this->client->request('GET', '/form/'.$id.'/');
        $form    = $crawler->filter('form')->form();
        $data    = $form->getValues();

        $data['mauticform[field_lastname]'] = $lastname;
        $data['mauticform[field_email]']    = $email;

        $form->setValues($data);

        $this->client->submit($form);
        $formModel = static::getContainer()->get('mautic.form.model.form');
        assert($formModel instanceof FormModel);

        $fomEntity = $formModel->getRepository()->find($id);

        $result = $this->em->getRepository(Submission::class)->findOneBy(['form' => $fomEntity], ['id' => 'DESC']);

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function getResultOfNewTable(FormEntity $form): array
    {
        $tableName  = MAUTIC_TABLE_PREFIX.'form_results_'.$form->getId().'_'.$form->getAlias();
        $connection = $this->em->getConnection();
        $sql        = "SELECT * FROM $tableName";
        $stmt       = $connection->prepare($sql);
        $result     = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    private function addEmailStat(
        Lead $lead,
        Email $emailEntity,
        string $emailAddress,
    ): Stat {
        $stat = new Stat();
        $stat->setEmailAddress($emailAddress);
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime('2023-07-21'));
        $stat->setEmail($emailEntity);
        $stat->setIsRead(true);
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function setEmailUnique(bool $isUnique = true): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $entity     = $fieldModel->getRepository()->findOneBy(['alias' => 'email']);
        assert($entity instanceof LeadField);
        $entity->setIsUniqueIdentifer($isUnique);
        $fieldModel->saveEntity($entity);
    }

    /**
     * @param array <string> $fieldsToAnonymize
     * @param array <string> $fieldsToDelete
     * @param bool|int       $pseudonymize
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCampaignEvent(Campaign $campaign, string $name, array $fieldsToAnonymize, array $fieldsToDelete, $pseudonymize): Event
    {
        $campaignEvent = new Event();
        $campaignEvent->setCampaign($campaign);
        $campaignEvent->setType(self::EVENT_LEAD_TYPE);
        $campaignEvent->setEventType('action');
        $campaignEvent->setTriggerMode('immediate');
        $campaignEvent->setName($name);
        $campaignEvent->setProperties(
            [
                'pseudonymize'      => $pseudonymize,
                'fieldsToAnonymize' => $fieldsToAnonymize,
                'fieldsToDelete'    => $fieldsToDelete,
            ]
        );
        $this->em->persist($campaignEvent);
        $this->em->flush();

        return $campaignEvent;
    }

    private function createCampaign(LeadList $list): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $campaign->setDateAdded(new \DateTime());
        $campaign->setDateModified(new \DateTime());
        $campaign->setIsPublished(true);
        $campaign->addList($list);
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @param array <Lead> $leads
     */
    private function addLeadToList(array $leads, LeadList $list): void
    {
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        assert($leadModel instanceof LeadModel);
        foreach ($leads as $lead) {
            $leadModel->addToLists($lead, $list);
        }
    }

    private function createLeadList(string $nameList, string $alias='test_list_example'): LeadList
    {
        $list = new LeadList();
        $list->setName($nameList);
        $list->setAlias($alias);
        $list->setDateAdded(new \DateTime());
        $list->setDateModified(new \DateTime());
        $list->setCreatedBy(1);
        $list->setModifiedBy(null);
        $list->setFilters([]);
        $list->setPublicName($nameList);
        $list->setIsPublished(true);
        $this->em->persist($list);
        $this->em->flush();

        return $list;
    }

    private function createLead(
        string $name = 'Test',
        string $lastname= 'last name',
        string $email = 'example@test.com',
        string $address = 'Address Line 1',
    ): Lead {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setFirstname($name);
        if (!empty($lastname)) {
            $lead->setLastname($lastname);
        }
        $lead->setLastname($lastname);
        $lead->setDateAdded(new \DateTime());
        $lead->setDateIdentified(new \DateTime());
        $lead->setLastActive(new \DateTime());
        $lead->setPoints(0);
        $lead->setIsPublished(true);
        $lead->setLastActive(new \DateTime());
        $lead->setAddress1($address);
        $lead->setCountry('US');
        $lead->setCity('New York');
        $lead->setZipcode('10001');
        $lead->setDateAdded(new \DateTime());
        $lead->setDateModified(new \DateTime());
        $lead->setCreatedBy(1);
        $lead->setModifiedBy(null);

        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        \assert($leadModel instanceof LeadModel);
        $leadModel->getRepository()->saveEntities([$lead]);

        $auditLogModel = static::getContainer()->get('mautic.core.model.auditlog');
        $auditLogModel->writeToLog([
            'object'   => 'lead',
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'action'   => 'create',
            'message'  => 'Lead created',
            'details'  => [
                'lead' => ['a', 'email' => $lead->getEmail()],
            ],
        ]);

        $lead->setPoints(1);
        $lead->setIsPublished(true);
        $leadModel->getRepository()->saveEntity($lead);

        $auditLogModel->writeToLog(
            [
                'object'   => 'lead',
                'objectId' => $lead->getId(),
                'bundle'   => 'lead',
                'action'   => 'update',
                'message'  => 'Lead updated',
                'details'  => [
                    'lead' => ['a', 'email' => $lead->getEmail()],
                ],
            ]
        );

        return $this->em->getRepository(Lead::class)->find($lead->getId());
    }

    public function testAllFieldsToDeleteData(): void
    {
        $newField = $this->newField('new_field1', 'New Field To Delete', 32);
        $this->client->request('GET', self::URI_EVENT_NEW, [], [], $this->createAjaxHeaders());
        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $values       = $form->getValues();

        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $getFieldToDeleteTrue = $this->getFieldChoices();
        $nameEvent            = 'Anonymize User Data Test';
        $values               = array_merge($values, $this->getDefaultValuesForm([], array_values($getFieldToDeleteTrue), $nameEvent));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString($nameEvent, $response->getContent());
        Assert::assertContains($newField->getId(), $responseData['event']['properties']['fieldsToDelete']);
    }

    private function newField(string $alias = 'new_field', string $label = 'New Field', int $size = 64): LeadField
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $newField = new LeadField();
        $newField->setAlias($alias);
        $newField->setLabel($label);
        $newField->setType('text');
        $newField->setIsUniqueIdentifer(false);
        $newField->setIsPublished(true);
        $newField->setCharLengthLimit($size);
        $fieldModel->saveEntity($newField);

        return $newField;
    }

    /**
     * @return array<string, string>
     */
    private function getFieldChoices(bool $checkIsUniqueField = true, bool $validLimitChar = false): array
    {
        $findBy['type'] = self::FIELD_TYPE_ALLOWED;
        if ($checkIsUniqueField) {
            $findBy['isUniqueIdentifer'] = false;
        }
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $leadFields    = $fieldModel->getRepository()->findBy($findBy);
        $choices       = [];
        foreach ($leadFields as $field) {
            $choices[$field->getLabel()] = (string) $field->getId();
        }

        return $choices;
    }

    /**
     * @param array<string> $fieldsToAnonymize
     * @param array<string> $fieldsToDelete
     *
     * @return array<string, array<string>|int|string>
     */
    private function getDefaultValuesForm(array $fieldsToAnonymize, array $fieldsToDelete, string $name, bool $pseudonymize = false): array
    {
        return [
            'campaignevent[properties][pseudonymize]'      => ($pseudonymize) ? 1 : 0,
            'campaignevent[properties][fieldsToAnonymize]' => $fieldsToAnonymize,
            'campaignevent[properties][fieldsToDelete]'    => $fieldsToDelete,
            'campaignevent[type]'                          => self::EVENT_LEAD_TYPE,
            'campaignevent[eventType]'                     => 'action',
            'campaignevent[anchorEventType]'               => 'source',
            'campaignevent[triggerMode]'                   => 'immediate',
            'campaignevent[anchor]'                        => 'leadsource',
            'campaignevent[name]'                          => $name,
        ];
    }

    /**
     * @return string[]
     */
    protected function createAjaxHeaders(): array
    {
        return [
            'HTTP_Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'HTTP_X-CSRF-Token'     => $this->getCsrfToken('mautic_ajax_post'),
        ];
    }

    /**
     * @param int $campaignId
     *
     * @return array<string, \Symfony\Component\Console\Tester\CommandTester>
     */
    private function runCampaignUpdateTrigger(int $campaignId): array
    {
        $resultRunCommandCampaign1 = $this->testSymfonyCommand('mautic:campaigns:update');
        $resultRunCommandCampaign2 = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaignId]);

        return [
            'resultRunCommandCampaign1' => $resultRunCommandCampaign1,
            'resultRunCommandCampaign2' => $resultRunCommandCampaign2,
        ];
    }
}
