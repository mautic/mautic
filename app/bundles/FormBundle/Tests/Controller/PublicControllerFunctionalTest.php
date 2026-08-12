<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('database')]
final class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testGenerateActionIsIndependentFromMauticTracking(): void
    {
        $form = $this->createForm();
        $form->setIsPublished(true);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/form/generate.js?id={$form->getId()}");

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/javascript; charset=UTF-8');
        $content = (string) $this->client->getResponse()->getContent();
        $this->assertStringContainsString('mauticform_wrapper_companylookuptest', $content);
        $this->assertStringContainsString("/form/submit?formId={$form->getId()}", $content);
        $this->assertStringContainsString('media/js/mautic-form.js', $content);
        $this->assertStringContainsString('MauticSDK.onLoad()', $content);
        $this->assertStringNotContainsString('MauticJS', $content);
        $this->assertStringNotContainsString('mtc_id', $content);
        $this->assertStringNotContainsString('mautic_device_id', $content);
        $this->assertStringNotContainsString('/mtc.js', $content);
        $this->assertStringNotContainsString('/mautic-essential.js', $content);
        $this->assertStringNotContainsString('/mautic-tracking.js', $content);
    }

    public function testLookupActionWithNoLookupFormField(): void
    {
        $this->makeRequest(['string' => 'Company']);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        $this->assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), (string) $clientResponse->getContent());
    }

    public function testLookupActionWithInvalidLookupFormField(): void
    {
        $this->makeRequest(['string' => 'Company', 'formId' => 3]);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        $this->assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), (string) $clientResponse->getContent());
    }

    public function testLookupActionWithTooFewLetters(): void
    {
        $form = $this->createForm();

        $this->makeRequest(['string' => 'Co', 'formId' => $form->getId()]);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        $this->assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), (string) $clientResponse->getContent());
    }

    public function testLookupActionWithCompanyData(): void
    {
        $this->createCompany('Unicorn A');
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B', 'Boston', 'Massachusetts');
        $form     = $this->createForm();

        $this->makeRequest(['search' => 'Company', 'formId' => $form->getId()]);
        $clientResponse = $this->client->getResponse();

        self::assertResponseIsSuccessful();
        $this->assertSame([
            [
                'id'           => (string) $companyA->getId(),
                'companyname'  => 'Company A',
                'companycity'  => null,
                'companystate' => null,
            ], [
                'id'           => (string) $companyB->getId(),
                'companyname'  => 'Company B',
                'companycity'  => 'Boston',
                'companystate' => 'Massachusetts',
            ],
        ], json_decode($clientResponse->getContent(), true));
    }

    /**
     * @param mixed[] $payload
     */
    private function makeRequest(array $payload): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/form/company-lookup/autocomplete',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );
    }

    private function createCompany(string $name, ?string $city = null, ?string $state = null): Company
    {
        $company = new Company();
        $company->setName($name);
        $company->setCity($city);
        $company->setState($state);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    private function createForm(): Form
    {
        $field = new Field();
        $field->setAlias('company-lookup');
        $field->setLabel('Company');
        $field->setType('companyLookup');

        $form = new Form();
        $form->setName('Company Lookup Test');
        $form->setAlias('company-lookup-test');
        $form->addField(0, $field);
        $field->setForm($form);

        $this->em->persist($field);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }
}
