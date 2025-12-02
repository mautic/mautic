<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;

class SourceControllerTest extends MauticMysqlTestCase
{
    private const ACCESS_DENIED      = 'You do not have access to the requested area\/action';
    private const NEW_FORMS_URL      = '/s/campaigns/sources/new/1?sourceType=forms';
    private const DELETE_FORMS_URL   = '/s/campaigns/sources/delete/1?sourceType=forms';

    public function testNewActionWithInvalidSourceType(): void
    {
        $this->client->xmlHttpRequest('GET', '/s/campaigns/sources/new/1?sourceType=invalid');
        $response = $this->client->getResponse();
        $this->assertStringContainsString(self::ACCESS_DENIED, $response->getContent());
    }

    public function testNewActionWithNonAjaxRequest(): void
    {
        $this->client->request('GET', self::NEW_FORMS_URL);
        $response = $this->client->getResponse();
        $this->assertStringContainsString(self::ACCESS_DENIED, $response->getContent());
    }

    public function testNewActionFormCancelled(): void
    {
        $formData = [
            'campaign_leadsource' => [
                'sourceType' => 'forms',
            ],
            'submit' => '1',
            'cancel' => '1',
        ];

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest('POST', self::NEW_FORMS_URL, $formData);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $json = json_decode($response->getContent(), true);
        if (is_array($json)) {
            $this->assertArrayHasKey('success', $json, 'Response should contain success key');
            $this->assertArrayHasKey('mauticContent', $json, 'Response should contain mauticContent key');
            $this->assertJsonResponseEquals('success', 0, $json);
            $this->assertJsonResponseEquals('mauticContent', 'campaignSource', $json);
            // When cancelled, we expect the form to be returned with error state
            $this->assertArrayHasKey('newContent', $json, 'Response should contain form HTML when validation fails');
        } else {
            $this->fail('Response is not valid JSON: '.$response->getContent());
        }
    }

    public function testNewActionFormInvalid(): void
    {
        $formData = [
            'campaign_leadsource' => [
                'sourceType' => 'forms',
            ],
            'submit' => '1',
        ];

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest('POST', self::NEW_FORMS_URL, $formData);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $json = json_decode($response->getContent(), true);
        if (is_array($json)) {
            $this->assertArrayHasKey('success', $json, 'Response should contain success key');
            $this->assertJsonResponseEquals('success', 0, $json);
            $this->assertArrayHasKey('mauticContent', $json, 'Response should contain mauticContent key');
            $this->assertArrayHasKey('newContent', $json, 'Response should contain form HTML when validation fails');
        } else {
            $this->fail('Response is not valid JSON: '.$response->getContent());
        }
    }

    public function testDeleteActionWithGetRequest(): void
    {
        $this->client->xmlHttpRequest('GET', self::DELETE_FORMS_URL);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $json = json_decode($response->getContent(), true);
        if (is_array($json)) {
            $this->assertArrayHasKey('success', $json, 'Response should contain success key');
            $this->assertJsonResponseEquals('success', 0, $json);
        } else {
            $this->fail('Response is not valid JSON: '.$response->getContent());
        }
    }

    public function testTwoSourcesWithSameName(): void
    {
        $form1 = new Form();
        $form1->setName('test');
        $form1->setAlias('test');
        $form1->setFormType('campaign');

        $form2 = new Form();
        $form2->setName('test');
        $form2->setAlias('test');
        $form2->setFormType('campaign');

        $this->em->persist($form1);
        $this->em->persist($form2);

        $this->em->flush();
        $this->em->detach($form1);
        $this->em->detach($form2);

        $this->client->xmlHttpRequest('GET', '/s/campaigns/sources/new/random_object_id?sourceType=forms');
        $clientResponse  = $this->client->getResponse();
        $responseContent = $clientResponse->getContent();
        $this->assertResponseIsSuccessful($responseContent);

        $html = json_decode($responseContent, true)['newContent'];
        $this->assertStringContainsString("<option value=\"{$form1->getId()}\">test ({$form1->getId()})</option>", $html);
        $this->assertStringContainsString("<option value=\"{$form2->getId()}\">test ({$form2->getId()})</option>", $html);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function assertJsonResponseHasKey(string $key, array $json, string $message = ''): void
    {
        $this->assertIsArray($json, 'Response is not a valid JSON array');
        $this->assertArrayHasKey($key, $json, $message);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function assertJsonResponseEquals(string $key, mixed $expected, array $json, string $message = ''): void
    {
        $this->assertJsonResponseHasKey($key, $json, $message);
        $this->assertEquals($expected, $json[$key], $message);
    }
}
