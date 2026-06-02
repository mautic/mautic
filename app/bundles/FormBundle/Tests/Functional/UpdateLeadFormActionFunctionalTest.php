<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLeadFormActionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @param array<string, mixed> $leadData
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $actionProperties
     * @param array<string, mixed> $expectedLeadData
     */
    #[DataProvider('updateLeadActionDataProvider')]
    public function testUpdateLeadAction(array $leadData, array $formData, array $actionProperties, array $expectedLeadData): void
    {
        $lead = new Lead();
        foreach ($leadData as $field => $value) {
            $method = 'set'.ucfirst($field);
            if (method_exists($lead, $method)) {
                $lead->$method($value);
            }
        }

        $this->em->persist($lead);
        $this->em->flush();

        $actionProperties = array_merge($this->getDefaultActionProperties(), $actionProperties);

        $form = $this->createFormViaApi('Test form', $actionProperties);

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$form->getId()}");
        $formCrawler = $crawler->filter('form[id=mauticform_testform]');

        if (0 === $formCrawler->count()) {
            $this->fail('Form not found: '.$this->client->getResponse()->getContent());
        }
        $formElement = $formCrawler->form();

        $formValues = [];
        foreach ($formData as $field => $value) {
            $formValues["mauticform[$field]"] = $value;
        }

        $formElement->setValues($formValues);
        $this->client->submit($formElement);

        $this->em->clear();
        foreach ($expectedLeadData as $field => $value) {
            $leadFieldValue = $lead->getFieldValue($field);
            if ('{datetime=now}' === $value) {
                $actualValue = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $leadFieldValue);
                $diff        = abs($actualValue->getTimestamp() - (new \DateTime())->getTimestamp());
                $this->assertLessThan(60, $diff, "The {$field} is not within 60 seconds of now.");
            } else {
                $this->assertEquals($value, $leadFieldValue ?? null, "Field $field does not match");
            }
        }
    }

    /**
     * @return iterable<string, array<string, array<string, mixed>>>
     */
    public static function updateLeadActionDataProvider(): iterable
    {
        yield 'Set country to Poland' => [
            'leadData' => [
                'email'     => 'test@test.com',
                'firstname' => 'Robert',
            ],
            'formData' => [
                'email'     => 'test@test.com',
                'name'      => 'Robert Updated',
            ],
            'actionProperties' => [
                'country' => 'Poland',
            ],
            'expectedLeadData' => [
                'firstname' => 'Robert Updated',
                'country'   => 'Poland',
            ],
        ];
        yield 'Update attribution date with token' => [
            'leadData' => [
                'email' => 'token@test.com',
            ],
            'formData' => [
                'email' => 'token@test.com',
            ],
            'actionProperties' => [
                'attribution_date' => '{datetime=now}',
            ],
            'expectedLeadData' => [
                'attribution_date' => '{datetime=now}',
            ],
        ];
        yield 'Form data wins over action properties for same field' => [
            'leadData' => [
                'email'     => 'conflict@test.com',
                'firstname' => 'Original Name',
            ],
            'formData' => [
                'email'     => 'conflict@test.com',
                'name'      => 'Form Updated Name',
            ],
            'actionProperties' => [
                'firstname' => 'Action Updated Name',
            ],
            'expectedLeadData' => [
                'firstname' => 'Form Updated Name',
            ],
        ];
        yield 'Empty form data should not override existing lead data' => [
            'leadData' => [
                'email'     => 'preserve@test.com',
                'firstname' => 'Keep This Name',
                'lastname'  => 'Keep This Last Name',
                'city'      => 'Keep This City',
            ],
            'formData' => [
                'email'     => 'preserve@test.com',
            ],
            'actionProperties' => [
                'country' => 'Poland',
            ],
            'expectedLeadData' => [
                'firstname' => 'Keep This Name',
                'lastname'  => 'Keep This Last Name',
                'city'      => 'Keep This City',
                'country'   => 'Poland',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $actionProperties
     */
    public function createFormViaApi(string $name, array $actionProperties): Form
    {
        $formPayload = [
            'name'        => $name,
            'description' => '',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'leadField'    => 'email',
                    'mappedField'  => 'email',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Your name',
                    'type'         => 'text',
                    'alias'        => 'name',
                    'leadField'    => 'firstname',
                    'mappedField'  => 'firstname',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Country',
                    'type'         => 'text',
                    'alias'        => 'country',
                    'leadField'    => 'country',
                    'mappedField'  => 'country',
                    'mappedObject' => 'contact',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'actions' => [
                [
                    'name'        => 'Update lead',
                    'description' => '',
                    'type'        => 'lead.updatelead',
                    'order'       => 1,
                    'properties'  => $actionProperties,
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $formId   = $response['form']['id'];

        return $this->em->getRepository(Form::class)->find($formId);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultActionProperties(): array
    {
        return [
            'title'            => null,
            'firstname'        => null,
            'lastname'         => null,
            'company'          => null,
            'position'         => null,
            'email'            => null,
            'mobile'           => null,
            'phone'            => null,
            'points'           => null,
            'fax'              => null,
            'address1'         => null,
            'address2'         => null,
            'city'             => null,
            'state'            => null,
            'zipcode'          => null,
            'country'          => null,
            'preferred_locale' => null,
            'timezone'         => null,
            'last_active'      => null,
            'attribution_date' => null,
            'attribution'      => null,
            'website'          => null,
            'facebook'         => null,
            'foursquare'       => null,
            'instagram'        => null,
            'linkedin'         => null,
            'skype'            => null,
            'twitter'          => null,
        ];
    }
}
