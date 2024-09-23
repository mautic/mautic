<?php

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class CampaignActionAnonymizeUserDataSubscriberFormFunctionalTest extends MauticMysqlTestCase
{
    public const EVENT_LEAD_TYPE = 'lead.action_anonymizeuserdata';

    public const URI_EVENT_NEW = '/s/campaigns/events/new?type='.self::EVENT_LEAD_TYPE.'&eventType=action&campaignId=mautic_85edec486b8a978db4a63f22ef588c74efd85d9e';

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
        Assert::assertStringContainsString('Anonymize User Data from fields', $response->getContent());
        Assert::assertStringContainsString('Zip Code', $response->getContent());
        Assert::assertStringContainsString('Address Line 1', $response->getContent());
        Assert::assertStringContainsString('Instagram', $response->getContent());
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
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $entity     = $fieldModel->getRepository()->findOneBy(['alias' => 'instagram']);
        $entity->setIsUniqueIdentifer(true);
        $fieldModel->saveEntity($entity);
        $uri = self::URI_EVENT_NEW;
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertStringNotContainsString('Instagram', $responseData['newContent']);
    }

    /**
     * @param array<string> $fieldsToAnonymize
     * @param array<string> $fieldsToDelete
     *
     * @return array<string, string>
     */
    private function getDefaultValuesForm(array $fieldsToAnonymize, array $fieldsToDelete): array
    {
        return [
            'campaignevent[properties][pseudonymize]'      => '1',
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
