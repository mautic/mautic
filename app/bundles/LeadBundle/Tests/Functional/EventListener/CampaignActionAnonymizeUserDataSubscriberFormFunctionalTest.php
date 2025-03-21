<?php

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Company;
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
        $this->assertCount(1, $matches[0], $response->getContent());
    }

    public function testAnonymizeUserDataAction(): void
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
        $values       = array_merge($values, $this->getDefaultValuesForm(['11', '2'], ['3', '5']));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString('Anonymize User Data Test', $response->getContent());

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
        $values                        = array_merge($values, $this->getDefaultValuesForm(['2', '5'], ['4']));
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
        Assert::assertStringContainsString('Anonymize User Data Updated Test', $response->getContent());
        Assert::assertContains(4, $responseData['event']['properties']['fieldsToDelete']);
        Assert::assertContains(2, $responseData['event']['properties']['fieldsToAnonymize']);
        Assert::assertContains(5, $responseData['event']['properties']['fieldsToAnonymize']);
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
        $values         = array_merge($values, $this->getDefaultValuesForm($fieldsToAnonymize, $fieldsToDelete));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(0, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString('Anonymize User Data Test', $responseData['newContent']);
        Assert::assertStringContainsString('The field(s) can&#039;t be both deleted and anonymized: ', $responseData['newContent']);
        Assert::assertStringContainsString('<li>First Name</li>', $responseData['newContent']);
        Assert::assertStringContainsString('<li>Last Name</li>', $responseData['newContent']);
    }

    public function testAnonymizeUserDataActionInvalidWithEmptyFields(): void
    {
        $form   = $this->baseRequest();
        $values = $form->getValues();
        $values = array_merge($values, $this->getDefaultValuesForm([], []));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(0, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString('Anonymize User Data Test', $responseData['newContent']);
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

    public function testAllFieldsToAnonymizeData()
    {
        $newField = $this->newField('new_field', 'New Field', 32);
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
        $getFieldChoices  = $this->getFieldChoices(false, true);
        $getFieldToDelete = $this->getFieldChoices(true);

        $values       = array_merge($values, $this->getDefaultValuesForm(array_values($getFieldChoices), []));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString('Anonymize User Data Test', $response->getContent());
        Assert::assertNotContains($newField->getId(), $responseData['event']['properties']['fieldsToAnonymize']);
    }

    public function testPseudonymizeData(): void
    {
        // Set email field as unique to duplicate the email
        $this->setEmailUnique();
        $email1 = 'foo@baa.com';
        $email2 = 'jhondoe@test.com';
        $lead1  = $this->createLead('Test1', 'Lastname1', $email1);
        $lead2  = $this->createLead('Test2', 'Lastname2', $email1);
        $lead3  = $this->createLead('Test3', 'Lastname3', $email2);

        $list = $this->createLeadList('Test List');
        $this->addLeadToList([$lead1, $lead2, $lead3], $list);

        $campaign = $this->createCampaign($list);

        $emailEntity = new Email();
        $emailEntity->setSubject('Test Email');
        $emailEntity->setFromName('Test');
        $emailEntity->setName('Test Email');
        $this->em->persist($emailEntity);
        $this->em->flush();
        $emailStat1       = $this->addEmailStat($lead1, $emailEntity, $email1);
        $emailStat2       = $this->addEmailStat($lead2, $emailEntity, $email1);
        $emailStat3       = $this->addEmailStat($lead3, $emailEntity, $email2);
        $getFieldChoices  = $this->getFieldChoices(false, true);

        // Fields Anonymize: First Name, Last Name
        // Fields to deleted: Primary Company, Position, Address Line 1
        $event = $this->createCampaignEvent($campaign, 'Anonymize User Data Test', ['2', '3', '6'], ['4', '5', '11'], true);

        // add event
        $campaign->addEvent('lead.action_anonymizeuserdata', $event);
        $this->em->flush();
        $this->em->clear();

        $this->createTestTable($email1, $email2, $email1);

        $resultRunCommandCampaign1 = $this->testSymfonyCommand('mautic:campaigns:update');
        $resultRunCommandCampaign2 = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        $newLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $newLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $newLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $resultFormTable = $this->getResultOfNewTable();
        $this->assertNotEmpty($resultFormTable);
        $this->assertNotContains($lead1->getEmail(), $resultFormTable);
        $this->assertContains($newLead1->getEmail(), $resultFormTable);

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
    }

    public function testAnonymizeData(): void
    {
        // Set email field as unique to duplicate the email
        $this->setEmailUnique();
        $email1 = 'foo@baa.com';
        $email2 = 'jhondoe@test.com';
        $lead1  = $this->createLead('Test1', 'Lastname1', $email1);
        $lead2  = $this->createLead('Test2', 'Lastname2', $email1);
        $lead3  = $this->createLead('Test3', 'Lastname3', $email2);

        $list = $this->createLeadList('Test List');
        $this->addLeadToList([$lead1, $lead2, $lead3], $list);

        $campaign = $this->createCampaign($list);

        $emailEntity = new Email();
        $emailEntity->setSubject('Test Email');
        $emailEntity->setFromName('Test');
        $emailEntity->setName('Test Email');
        $this->em->persist($emailEntity);
        $this->em->flush();
        $emailStat1       = $this->addEmailStat($lead1, $emailEntity, $email1);
        $emailStat2       = $this->addEmailStat($lead2, $emailEntity, $email1);
        $emailStat3       = $this->addEmailStat($lead3, $emailEntity, $email2);
        $getFieldChoices  = $this->getFieldChoices(false, true);

        // Fields Anonymize: First Name, Last Name
        // Fields to deleted: Primary Company, Position, Address Line 1
        $event = $this->createCampaignEvent($campaign, 'Anonymize User Data Test', ['2', '3', '6'], ['4', '5', '11'], false);

        // add event
        $campaign->addEvent('lead.action_anonymizeuserdata', $event);
        $this->em->flush();
        $this->em->clear();

        $this->createTestTable($email1, $email2, $email1);

        $resultRunCommandCampaign1 = $this->testSymfonyCommand('mautic:campaigns:update');
        $resultRunCommandCampaign2 = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        $newLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $newLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $newLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $resultFormTable = $this->getResultOfNewTable();

        $this->assertNotEmpty($resultFormTable);
        $this->assertNotContains($lead1->getEmail(), $resultFormTable);
        $this->assertNotContains($newLead1->getEmail(), $resultFormTable);

        $this->assertNotSame($newLead1->getEmail(), $newLead2->getEmail());
        $this->assertNotSame($newLead1->getEmail(), $lead1->getEmail());
        $this->assertNotSame($newLead1->getFirstname(), $lead1->getFirstname());
        $this->assertNotSame($newLead3->getLastname(), $lead3->getLastname());
        $this->assertNotEmpty($lead1->getAddress1());
        $this->assertEmpty($newLead1->getAddress1());
    }

    private function createTestTable(string $email1, string $email2, string $email3): void
    {
        // Define table name with the prefix (replace 'your_prefix_' with the desired prefix)
        $tableName = MAUTIC_TABLE_PREFIX.self::TEST_TABLE_NAME;

        // The SQL statement to create the table
        $sql = "
        CREATE TABLE IF NOT EXISTS `$tableName` (
            `submission_id` INT AUTO_INCREMENT NOT NULL,
            `form_id` INT NOT NULL,
            `test_email` longtext NOT NULL,
            PRIMARY KEY (`submission_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

        // Execute the SQL command
        $connection = $this->em->getConnection();
        $connection->executeStatement($sql);

        // Optionally insert some test data
        $sql = "INSERT INTO `$tableName` (`form_id`, `test_email`) VALUES (:formId, :testEmail)";
        $connection->executeStatement("
    INSERT INTO `$tableName` (`form_id`, `test_email`) VALUES
    (1, '".$email1."'),
    (2, '".$email2."'),
    (3, '".$email3."');
");
    }

    private function getResultOfNewTable(): array
    {
        $tableName  = MAUTIC_TABLE_PREFIX.self::TEST_TABLE_NAME;
        $connection = $this->em->getConnection();
        $sql        = "SELECT submission_id as id, test_email FROM $tableName";
        $stmt       = $connection->prepare($sql);
        $result     = $stmt->executeQuery();

        return $result->fetchAllKeyValue();
    }

    private function addEmailStat(
        Lead $lead,
        Email $emailEntity,
        string $emailAddress
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

    private function createCampaignEvent($campaign, string $name, array $fieldsToAnonymize, array $fieldsToDelete, bool $pseudonymize = false): Event
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
        $list->setModifiedBy(1);
        $list->setFilters([]);
        $list->setPublicName($nameList);
        $list->setIsPublished(true);
        $this->em->persist($list);
        $this->em->flush();

        return $list;
    }

    private function createLead(
        string $name = 'Test',
        $lastname= 'last name',
        string $email = 'example@test.com',
        string $address = 'Address Line 1',
    ): Lead {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setFirstname($name);
        $lead->setLastname($lastname);
        $lead->setDateAdded(new \DateTime());
        $lead->setDateIdentified(new \DateTime());
        $lead->setLastActive(new \DateTime());
        $lead->setPoints(0);
        $lead->setIsPublished(true);
        $lead->setLastActive(new \DateTime());
        $lead->setAddress1($address);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    public function testAllFieldsToDeleteData(): void
    {
        $newField = $this->newField('new_field', 'New Field', 32);
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
        $getFieldChoices  = $this->getFieldChoices(false, true);
        $getFieldToDelete = $this->getFieldChoices(true);

        $values       = array_merge($values, $this->getDefaultValuesForm([], array_values($getFieldToDelete)));
        $form->setValues($values);
        $this->client->submit($form, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
        Assert::assertStringContainsString('Anonymize User Data Test', $response->getContent());
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

    private function getFieldChoices(bool $checkIsUniqueField=true, bool $validLimitChar = false): array
    {
        $findBy['type'] = self::FIELD_TYPE_ALLOWED;
        if ($checkIsUniqueField) {
            $findBy['isUniqueIdentifer'] = false;
        }
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        assert($fieldModel instanceof FieldModel);
        $leadFields    = $fieldModel->getRepository()->findBy($findBy);
        $columnsLength = $this->getLeadCompanyColumnsLenght();
        $choices       = [];
        foreach ($leadFields as $field) {
            if ($validLimitChar && $this->getCharLengthLimit($field, $columnsLength) < 64) {
                continue;
            }
            $choices[$field->getLabel()] = $field->getId();
        }

        return $choices;
    }

    private function getCharLengthLimit(LeadField $leadField, array $leadsCompanyColumnsLength): int
    {
        $alias = $leadField->getAlias();
        $key   = 'companies';
        if ('lead' === $leadField->getObject()) {
            $key = 'leads';
        }
        if (isset($leadsCompanyColumnsLength[$key][$alias])) {
            return $leadsCompanyColumnsLength[$key][$alias];
        }

        return $leadField->getCharLengthLimit();
    }

    private function getLeadCompanyColumnsLenght(): array
    {
        $entityManager   = static::getContainer()->get('doctrine.orm.entity_manager');
        $leadMetadata    = $entityManager->getClassMetadata(Lead::class);
        $companyMetadata = $entityManager->getClassMetadata(Company::class);
        $columnsLength   = [
            'leads'     => [],
            'companies' => [],
        ];
        foreach ($leadMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['leads'][$fieldName] = $fieldMapping['length'];
            }
        }

        foreach ($companyMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['companies'][$fieldName] = $fieldMapping['length'];
            }
        }

        return $columnsLength;
    }

    /**
     * @param array<string> $fieldsToAnonymize
     * @param array<string> $fieldsToDelete
     *
     * @return array<string, string>
     */
    private function getDefaultValuesForm(array $fieldsToAnonymize, array $fieldsToDelete, bool $pseudonymize = false): array
    {
        return [
            'campaignevent[properties][pseudonymize]'      => ($pseudonymize) ? '1' : '0',
            'campaignevent[properties][fieldsToAnonymize]' => $fieldsToAnonymize,
            'campaignevent[properties][fieldsToDelete]'    => $fieldsToDelete,
            'campaignevent[type]'                          => self::EVENT_LEAD_TYPE,
            'campaignevent[eventType]'                     => 'action',
            'campaignevent[anchorEventType]'               => 'source',
            'campaignevent[triggerMode]'                   => 'immediate',
            'campaignevent[anchor]'                        => 'leadsource',
            'campaignevent[name]'                          => 'Anonymize User Data Test',
        ];
    }
}
