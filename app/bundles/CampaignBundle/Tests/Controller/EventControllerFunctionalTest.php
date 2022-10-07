<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

final class EventControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider fieldAndValueProvider
     */
    public function testCreateContactConditionOnStateField(string $field, string $value): void
    {
        // Fetch the campaign condition form.
        $uri = '/s/campaigns/events/new?type=lead.field_value&eventType=condition&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=leadsource&anchorEventType=source';
        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());

        // Get the form HTML element out of the response, fill it in and submit.
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[anchor]'               => 'leadsource',
                'campaignevent[properties][field]'    => $field,
                'campaignevent[properties][operator]' => '=',
                'campaignevent[properties][value]'    => $value,
                'campaignevent[type]'                 => 'lead.field_value',
                'campaignevent[eventType]'            => 'condition',
                'campaignevent[anchorEventType]'      => 'source',
                'campaignevent[campaignId]'           => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $this->client->request($form->getMethod(), $form->getUri(), $form->getPhpValues(), [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk(), $response->getContent());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame(1, $responseData['success'], print_r(json_decode($response->getContent(), true), true));
    }

    /**
     * @return string[][]
     */
    public function fieldAndValueProvider(): array
    {
        return [
            'country'  => ['country', 'India'],
            'region'   => ['state', 'Arizona'],
            'timezone' => ['timezone', 'Marigot'],
            'locale'   => ['preferred_locale', 'af'],
        ];
    }
}
