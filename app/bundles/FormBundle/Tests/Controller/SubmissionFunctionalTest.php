<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\LeadBundle\Entity\Company;
use Mautic\PageBundle\Entity\Page;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class SubmissionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback   = false;
    protected bool $authenticateApi = true;

    public function testRedirectPostAction(): void
    {
        $page = new Page();
        $page->setTitle('Test');
        $page->setAlias('test-form-redirect-target-page');
        $page->setCustomHtml('<!DOCTYPE html><html><head></head><body>Test</body></html>');
        $this->em->persist($page);
        $this->em->flush();
        $pageId = $page->getId();

        // Create the test form via API.
        $payload = [
            'name'               => 'Redirect post action test form',
            'description'        => 'Form created via submission test',
            'formType'           => 'standalone',
            'isPublished'        => true,
            'postAction'         => 'redirect',
            'postActionProperty' => '{pagelink='.$pageId.'}?foo=bar&lead={contactfield=id}&email={formfield=email}',

            'fields'      => [
                [
                    'label'     => 'Email',
                    'type'      => 'email',
                    'alias'     => 'email',
                    'leadField' => 'email',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);
        $formId   = $response['form']['id'];

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_redirectpostactiontestform]');

        $this->assertCount(1, $formCrawler);

        $form = $formCrawler->form();

        $form->setValues([
            'mauticform[email]' => 'john@doe.com',
        ]);

        $this->client->submit($form);
        $currentUrl = $this->client->getRequest()->getUri();

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Check the redirect
        $currentUrl = $this->client->getRequest()->getUri();
        $urlParts   = parse_url($currentUrl);
        parse_str($urlParts['query'], $queryParams);

        $this->assertEquals('/test-form-redirect-target-page', $urlParts['path']);
        // Test that the redirect didn't remove any additional URL parts
        $this->assertEquals('john@doe.com', $queryParams['email']);
        $this->assertGreaterThan(0, (int) $queryParams['lead']);
        $this->assertEquals('bar', $queryParams['foo']);
    }

    public function testRequiredConditionalFieldIfNotEmpty(): void
    {
        // Create the test form via API.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Country',
                    'type'      => 'country',
                    'alias'     => 'country',
                    'leadField' => 'country',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'     => 'return',
            'formAttributes' => 'class="foobar"',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);
        $formId   = $response['form']['id'];

        // Add conditional state field dependent on the country field:
        $patchPayload = [
            'fields' => [
                [
                    'label'      => 'State',
                    'type'       => 'select',
                    'alias'      => 'state',
                    'leadField'  => 'state',
                    'parent'     => $response['form']['fields'][0]['id'],
                    'isRequired' => true,
                    'conditions' => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties' => [
                        'syncList' => 1,
                        'multiple' => 0,
                    ],
                ],
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $this->assertStringContainsString(' class="foobar"', $crawler->html());
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[country]' => 'Australia',
            'mauticform[state]'   => 'Victoria',
        ]);
        $this->client->submit($form);

        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        /** @var SubmissionRepository $submissionRepository */
        $submissionRepository = $this->em->getRepository(Submission::class);

        // Ensure the submission was created properly.
        $submissions = $submissionRepository->findBy(['form' => $formId]);

        Assert::assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        Assert::assertSame([
            'country' => 'Australia',
            'state'   => 'Victoria',
        ], $submission->getResults());

        // A contact should be created by the submission.
        $contact = $submission->getLead();

        Assert::assertSame('Australia', $contact->getCountry());
        Assert::assertSame('Victoria', $contact->getState());

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony($this->configParams);
        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testRequiredConditionalFieldIfAllFieldsEmpty(): void
    {
        // Create the test form via API.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Country',
                    'type'      => 'country',
                    'alias'     => 'country',
                    'leadField' => 'country',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Add conditional state field dependent on the country field:
        $patchPayload = [
            'fields' => [
                [
                    'label'      => 'State',
                    'type'       => 'select',
                    'alias'      => 'state',
                    'leadField'  => 'state',
                    'parent'     => $response['form']['fields'][0]['id'],
                    'isRequired' => true,
                    'conditions' => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties' => [
                        'syncList' => 1,
                        'multiple' => 0,
                    ],
                ],
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[country]' => '',
            'mauticform[state]'   => '',
        ]);
        $this->client->submit($form);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();
        Assert::assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        Assert::assertSame([
            'country' => '',
        ], $submission->getResults());

        // A contact should be created by the submission.
        $contact = $submission->getLead();

        Assert::assertNull($contact->getCountry());
        Assert::assertNull($contact->getState());

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony($this->configParams);

        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testRequiredConditionalFieldIfRequiredStateShouldKickIn(): void
    {
        // Create the test form via API.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Country',
                    'type'      => 'country',
                    'alias'     => 'country',
                    'leadField' => 'country',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction' => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Add conditional state field dependent on the country field:
        $patchPayload = [
            'fields' => [
                [
                    'label'      => 'State',
                    'type'       => 'select',
                    'alias'      => 'state',
                    'leadField'  => 'state',
                    'parent'     => $response['form']['fields'][0]['id'],
                    'isRequired' => true,
                    'conditions' => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties' => [
                        'syncList' => 1,
                        'multiple' => 0,
                    ],
                ],
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, "/api/forms/{$formId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[country]' => 'Australia',
            'mauticform[state]'   => '',
        ]);
        $this->client->submit($form);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();

        // It should not create a submission now as the required field is now visible and empty.
        Assert::assertCount(0, $submissions);

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony($this->configParams);

        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testProgressiveFormsWithMaximumFieldsDisplayedAtTime(): void
    {
        // Create the test form via API.
        $payload = [
            'name'                      => 'Submission test form',
            'description'               => 'Form created via submission test',
            'formType'                  => 'standalone',
            'isPublished'               => true,
            'progressiveProfilingLimit' => 2,
            'fields'                    => [
                [
                    'label'                  => 'Email',
                    'type'                   => 'email',
                    'alias'                  => 'email',
                    'leadField'              => 'email',
                    'is_auto_fill'           => 1,
                    'show_when_value_exists' => 0,
                ],
                [
                    'label'                  => 'Firstname',
                    'type'                   => 'text',
                    'alias'                  => 'firstname',
                    'leadField'              => 'firstname',
                    'is_auto_fill'           => 1,
                    'show_when_value_exists' => 0,
                ],
                [
                    'label'                  => 'Lastname',
                    'type'                   => 'text',
                    'alias'                  => 'lastname',
                    'leadField'              => 'lastname',
                    'is_auto_fill'           => 1,
                    'show_when_value_exists' => 0,
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'                => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        // show just one text field
        $this->assertCount(1, $formCrawler->filter('.mauticform-text'));
    }

    public function testAddContactToCampaignByForm(): void
    {
        // Create the test form via API.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'campaign',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Email',
                    'type'      => 'email',
                    'alias'     => 'email',
                    'leadField' => 'email',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $campaignSources = ['forms' => [$formId => $formId]];

        /** @var CampaignModel $campaignModel */
        $campaignModel = static::getContainer()->get('mautic.campaign.model.campaign');

        $publishedCampaign = new Campaign();
        $publishedCampaign->setName('Published');
        $publishedCampaign->setIsPublished(true);
        $campaignModel->setLeadSources($publishedCampaign, $campaignSources, []);

        $unpublishedCampaign =  new Campaign();
        $unpublishedCampaign->setName('Unpublished');
        $unpublishedCampaign->setIsPublished(false);
        $campaignModel->setLeadSources($unpublishedCampaign, $campaignSources, []);

        $this->em->persist($publishedCampaign);
        $this->em->persist($unpublishedCampaign);
        $this->em->flush();

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[email]' => 'xx@xx.com',
        ]);
        $this->client->submit($form);

        $submissions = $this->em->getRepository(Lead::class)->findAll();
        Assert::assertCount(1, $submissions);
    }

    protected function beforeTearDown(): void
    {
        $tablePrefix = static::getContainer()->getParameter('mautic.db_table_prefix');

        if ($this->connection->createSchemaManager()->tablesExist("{$tablePrefix}form_results_1_submission")) {
            $this->connection->executeStatement("DROP TABLE {$tablePrefix}form_results_1_submission");
        }
    }

    public function testFetchFormSubmissionsApiIfPermissionNotGrantedForUser(): void
    {
        // Create the test form via API.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Country',
                    'type'      => 'country',
                    'alias'     => 'country',
                    'leadField' => 'country',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[country]' => 'Australia',
        ]);
        $this->client->submit($form);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();

        Assert::assertCount(1, $submissions);

        // Enable reboots so all the services and in-memory data are refreshed.
        $this->client->enableReboot();

        // fetch form submissions as Admin User
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $this->assertResponseIsSuccessful();
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $submission     = $response['submissions'][0];

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertSame($formId, $submission['form']['id']);
        Assert::assertGreaterThanOrEqual(1, $response['total']);

        // Create non admin user
        $user = $this->createUser();

        // Fetch form submissions as non-admin-user who don't have the permission to view submissions
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions", [], [], [
            'PHP_AUTH_USER' => $user->getUserIdentifier(),
            'PHP_AUTH_PW'   => $this->getUserPlainPassword(),
        ]);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    private function createUser(): User
    {
        $role = new Role();
        $role->setName('api_restricted');
        $role->setDescription('Api Permission Not Granted');
        $role->setIsAdmin(false);
        $role->setRawPermissions(['form:forms' => ['viewown']]);

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->em->getRepository(Role::class);
        $roleRepository->saveEntity($role);

        $user = new User();
        $user->setEmail('api.restricted@test.com');
        $user->setUsername('non-admin-user');
        $user->setFirstName('test');
        $user->setLastName('test');
        $user->setRole($role);

        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash($this->getUserPlainPassword()));

        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        $userRepo->saveEntities([$user]);

        return $user;
    }

    public function testCompanyLookupFieldSubmission(): void
    {
        $form = new Form();
        $form->setName('Submission test form');
        $form->setAlias('submissiontestform');
        $form->setFormType('standalone');
        $form->setIsPublished(true);

        $lookup = new Field();
        $lookup->setLabel('Company');
        $lookup->setAlias('company');
        $lookup->setMappedField('companyname');
        $lookup->setMappedObject('company');
        $lookup->setType('companyLookup');
        $lookup->setForm($form);

        $email = new Field();
        $email->setLabel('Email');
        $email->setAlias('email');
        $email->setMappedField('email');
        $email->setMappedObject('lead');
        $email->setType('email');
        $email->setForm($form);

        $form->addField(0, $lookup);
        $form->addField(1, $email);

        $company = new Company();
        $company->setName('Acquia');

        $this->em->persist($company);
        $this->em->persist($form);
        $this->em->persist($lookup);
        $this->em->persist($email);
        $this->em->flush();

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$form->getId()}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $htmlForm = $formCrawler->form();
        $htmlForm->setValues([
            'mauticform[company]' => 'Acquia',
            'mauticform[email]'   => 'leeloo@fifth.element',
        ]);
        $this->client->submit($htmlForm);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();
        Assert::assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        Assert::assertSame([
            'company' => 'Acquia',
            'email'   => 'leeloo@fifth.element',
        ], $submission->getResults());

        // A contact should be created by the submission.
        $contact = $submission->getLead();

        Assert::assertSame('Acquia', $contact->getCompany());
        Assert::assertSame($company->getId(), $contact->getCompanyChangeLog()->get(0)->getCompany());

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony($this->configParams);

        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$form->getId()}/delete");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testSendSubmissionWhenFieldHaveMysqlReservedWords(): void
    {
        // Create the test form.
        $payload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'All',
                    'type'      => 'text',
                    'alias'     => 'all',
                    'leadField' => 'firstname',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form:
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler, $crawler->html());
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[f_all]' => 'test',
        ]);
        $this->client->submit($form);

        // Ensure the submission was created properly.
        $submissions = $this->em->getRepository(Submission::class)->findAll();
        Assert::assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        Assert::assertSame([
            'f_all' => 'test',
        ], $submission->getResults());

        // A contact should be created by the submission.
        $contact = $submission->getLead();

        Assert::assertSame('test', $contact->getFirstname());

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony($this->configParams);

        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString('Results for Submission test form', $clientResponse->getContent());

        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    private function getUserPlainPassword(): string
    {
        return 'test-pass!23';
    }

    /**
     * @dataProvider formFieldValuesMappingDataProvider
     *
     * @param array<string, string> $submissionData
     * @param array<string, string> $expectedData
     */
    public function testFormFieldValuesMapping(array $submissionData, array $expectedData): void
    {
        $formPayload = [
            'name'        => 'Submission test form',
            'description' => 'Form created via submission test',
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
                    'label'        => 'Firstname',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'leadField'    => 'firstname',
                    'mappedField'  => 'firstname',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Lastname',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'leadField'    => 'lastname',
                    'mappedField'  => 'lastname',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Country',
                    'type'         => 'country',
                    'alias'        => 'country',
                    'leadField'    => 'country',
                    'mappedField'  => 'country',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Company name',
                    'type'         => 'text',
                    'alias'        => 'company_name',
                    'leadField'    => 'company',
                    'mappedField'  => 'company',
                    'mappedObject' => 'contact',
                ],
                [
                    'label'        => 'Company country',
                    'type'         => 'text',
                    'alias'        => 'company_country',
                    'leadField'    => 'companycountry',
                    'mappedField'  => 'companycountry',
                    'mappedObject' => 'company',
                ],
                [
                    'label'        => 'Company city',
                    'type'         => 'text',
                    'alias'        => 'company_city',
                    'leadField'    => 'companycity',
                    'mappedField'  => 'companycity',
                    'mappedObject' => 'company',
                ],
                [
                    'label'        => 'Message',
                    'type'         => 'textarea',
                    'alias'        => 'message',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        // Create the form
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        $formData = [];
        foreach ($submissionData as $key => $value) {
            $formData["mauticform[{$key}]"] = $value;
        }
        $form->setValues($formData);

        $this->client->submit($form);

        // Get form submissions via API
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $submissionsData = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('total', $submissionsData);
        $this->assertArrayHasKey('submissions', $submissionsData);
        $this->assertGreaterThan(0, count($submissionsData['submissions']));

        $latestSubmission = $submissionsData['submissions'][0];

        // Check if the submission data matches
        $this->assertArrayHasKey('results', $latestSubmission);
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $latestSubmission['results']);
            $this->assertEquals($value, $latestSubmission['results'][$key], "Failed asserting that '{$latestSubmission['results'][$key]}' matches expected '$value' for field '$key'");
        }

        // Check contact details
        $this->assertArrayHasKey('lead', $latestSubmission);
        $contact = $latestSubmission['lead'];
        $this->assertEquals($expectedData['email'], $contact['email']);
        $this->assertEquals($expectedData['firstname'], $contact['firstname']);
        $this->assertEquals($expectedData['lastname'], $contact['lastname']);
        $this->assertEquals($expectedData['country'], $contact['country']);
        $this->assertEquals($expectedData['company_name'], $contact['company']);

        // Check submission metadata
        $this->assertArrayHasKey('ipAddress', $latestSubmission);
        $this->assertArrayHasKey('dateSubmitted', $latestSubmission);
        $this->assertArrayHasKey('referer', $latestSubmission);

        // Get contact companies
        $this->client->request(Request::METHOD_GET, "/api/contacts/{$contact['id']}/companies");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $contactCompanies = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('total', $contactCompanies);
        $this->assertArrayHasKey('companies', $contactCompanies);
        $this->assertEquals(1, count($contactCompanies['companies']));

        // Check company details
        $this->assertEquals($expectedData['company_name'], $contactCompanies['companies'][0]['companyname']);
        $this->assertEquals($expectedData['company_city'], $contactCompanies['companies'][0]['companycity']);
        $this->assertEquals($expectedData['company_country'], $contactCompanies['companies'][0]['companycountry']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    /**
     * @return array<string, array{input: array<string, string>, expected: array<string, string>}>
     */
    public function formFieldValuesMappingDataProvider(): array
    {
        return [
            'normal_submission' => [
                'input' => [
                    'email'           => 'john@example.com',
                    'firstname'       => 'John',
                    'lastname'        => 'Doe',
                    'country'         => 'United States',
                    'company_name'    => 'Acme Inc',
                    'company_country' => 'United States',
                    'company_city'    => 'New York',
                    'message'         => 'Hello, this is a normal submission.',
                ],
                'expected' => [
                    'email'           => 'john@example.com',
                    'firstname'       => 'John',
                    'lastname'        => 'Doe',
                    'country'         => 'United States',
                    'company_name'    => 'Acme Inc',
                    'company_country' => 'United States',
                    'company_city'    => 'New York',
                    'message'         => 'Hello, this is a normal submission.',
                ],
            ],
            'special_characters' => [
                'input' => [
                    'email'           => 'jane@example.com',
                    'firstname'       => 'Jane',
                    'lastname'        => 'O\'Brien-Smith',
                    'country'         => 'Ireland',
                    'company_name'    => '"Super" R&D Company, Ltd.',
                    'company_country' => 'Ireland',
                    'company_city'    => 'Dublin',
                    'message'         => 'Super & Special',
                ],
                'expected' => [
                    'email'           => 'jane@example.com',
                    'firstname'       => 'Jane',
                    'lastname'        => 'O\'Brien-Smith',
                    'country'         => 'Ireland',
                    'company_name'    => '"Super" R&D Company, Ltd.',
                    'company_country' => 'Ireland',
                    'company_city'    => 'Dublin',
                    'message'         => 'Super & Special',
                ],
            ],
            'xss_attempt' => [
                'input' => [
                    'email'           => 'hacker@evil.com',
                    'firstname'       => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                    'lastname'        => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                    'country'         => 'Poland',
                    'company_name'    => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                    'company_country' => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                    'company_city'    => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                    'message'         => '<script>alert("XSS")</script>',
                ],
                'expected' => [
                    'email'           => 'hacker@evil.com',
                    'firstname'       => 'alert("XSS")',
                    'lastname'        => 'alert("XSS")',
                    'country'         => 'Poland',
                    'company_name'    => 'alert("XSS")',
                    'company_country' => 'alert("XSS")',
                    'company_city'    => 'alert("XSS")',
                    'message'         => 'alert("XSS")',
                ],
            ],
            'sql_injection_attempt' => [
                'input' => [
                    'email'           => 'sqlhacker@evil.com',
                    'firstname'       => "Robert'; DROP TABLE users; --",
                    'lastname'        => 'Tables',
                    'country'         => 'United States',
                    'company_name'    => "Malicious' Corp; DELETE FROM companies WHERE 1=1; --",
                    'company_country' => 'United States',
                    'company_city'    => 'SQL City',
                    'message'         => "Robert'; DROP TABLE messages; --",
                ],
                'expected' => [
                    'email'           => 'sqlhacker@evil.com',
                    'firstname'       => "Robert'; DROP TABLE users; --",
                    'lastname'        => 'Tables',
                    'country'         => 'United States',
                    'company_name'    => "Malicious' Corp; DELETE FROM companies WHERE 1=1; --",
                    'company_country' => 'United States',
                    'company_city'    => 'SQL City',
                    'message'         => "Robert'; DROP TABLE messages; --",
                ],
            ],
            'unicode_characters' => [
                'input' => [
                    'email'           => 'unicode@example.com',
                    'firstname'       => 'José',
                    'lastname'        => 'Martínez',
                    'country'         => 'Spain',
                    'company_name'    => '株式会社スマイル',
                    'company_country' => 'Japan',
                    'company_city'    => '東京',
                    'message'         => 'こんにちは、世界！',
                ],
                'expected' => [
                    'email'           => 'unicode@example.com',
                    'firstname'       => 'José',
                    'lastname'        => 'Martínez',
                    'country'         => 'Spain',
                    'company_name'    => '株式会社スマイル',
                    'company_country' => 'Japan',
                    'company_city'    => '東京',
                    'message'         => 'こんにちは、世界！',
                ],
            ],
        ];
    }

    /**
     * @dataProvider formCustomFieldsMappingDataProvider
     *
     * @param array<string, string> $submissionData
     * @param array<string, string> $expectedData
     */
    public function testFormCustomFieldsMapping(array $submissionData, array $expectedData): void
    {
        // Create new contact custom field
        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', [
            'label' => 'Animal',
            'alias' => 'animal',
            'type'  => 'text',
        ]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('field', $response);
        $contactCustomField = $response['field'];

        // Create form
        $formPayload = [
            'name'        => 'Submission test form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'What kind of animal are you?',
                    'type'         => 'text',
                    'alias'        => 'animal',
                    'leadField'    => 'animal',
                    'mappedField'  => 'animal',
                    'mappedObject' => 'contact',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        // Create the form
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        // Submit the form
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        $formData = [];
        foreach ($submissionData as $key => $value) {
            $formData["mauticform[{$key}]"] = $value;
        }
        $form->setValues($formData);

        $this->client->submit($form);

        // Get form submissions via API
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $submissionsData = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('total', $submissionsData);
        $this->assertArrayHasKey('submissions', $submissionsData);
        $this->assertGreaterThan(0, count($submissionsData['submissions']));

        $latestSubmission = $submissionsData['submissions'][0];

        // Check if the submission data matches
        $this->assertArrayHasKey('results', $latestSubmission);
        foreach ($expectedData as $key => $value) {
            $this->assertArrayHasKey($key, $latestSubmission['results']);
            $this->assertEquals($value, $latestSubmission['results'][$key], "Failed asserting that '{$latestSubmission['results'][$key]}' matches expected '$value' for field '$key'");
        }

        // Check contact details
        $this->assertArrayHasKey('lead', $latestSubmission);
        $submissionContact = $latestSubmission['lead'];

        // Get contact
        $this->client->request(Request::METHOD_GET, "/api/contacts/{$submissionContact['id']}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $contactResponse = json_decode($clientResponse->getContent(), true);
        $this->assertArrayHasKey('contact', $contactResponse);
        $contact = $contactResponse['contact'];

        $this->assertArrayHasKey('animal', $contact['fields']['core']);
        $animalField = $contact['fields']['core']['animal'];
        $this->assertEquals($expectedData['animal'], $animalField['value']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request(Request::METHOD_DELETE, "/api/fields/contact/{$contactCustomField['id']}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    /**
     * @return array<string, array{input: array<string, string>, expected: array<string, string>}>
     */
    public function formCustomFieldsMappingDataProvider(): array
    {
        return [
            'simple_value' => [
                'input' => [
                    'animal' => 'Dog',
                ],
                'expected' => [
                    'animal' => 'Dog',
                ],
            ],
            'special_characters' => [
                'input' => [
                    'animal' => 'Guinea-Pig & Hamster\'s "friend"',
                ],
                'expected' => [
                    'animal' => 'Guinea-Pig & Hamster\'s "friend"',
                ],
            ],
            'xss_attempt' => [
                'input' => [
                    'animal' => '<script>alert("XSS")</script><img src=x onerror=alert("XSS")>',
                ],
                'expected' => [
                    'animal' => 'alert("XSS")',
                ],
            ],
            'sql_injection' => [
                'input' => [
                    'animal' => "Cat'; DROP TABLE animals; --",
                ],
                'expected' => [
                    'animal' => "Cat'; DROP TABLE animals; --",
                ],
            ],
            'unicode_and_emoji' => [
                'input' => [
                    'animal' => '🐕 犬 🐈 猫',  // Dog and Cat in Japanese with emojis
                ],
                'expected' => [
                    'animal' => '🐕 犬 🐈 猫',
                ],
            ],
            'nested_tags' => [
                'input' => [
                    'animal' => '<div><span>Text</span></div>',
                ],
                'expected' => [
                    'animal' => 'Text',
                ],
            ],
            'incomplete_tags' => [
                'input' => [
                    'animal' => '<div><span>Text',
                ],
                'expected' => [
                    'animal' => 'Text',
                ],
            ],
            'null_byte' => [
                'input' => [
                    'animal' => "Dog\x00Cat",
                ],
                'expected' => [
                    'animal' => 'DogCat',
                ],
            ],
            'javascript_protocol' => [
                'input' => [
                    'animal' => '<a href="javascript:alert(\'XSS\')">Click me</a>',
                ],
                'expected' => [
                    'animal' => 'Click me',
                ],
            ],
            'css_expression' => [
                'input' => [
                    'animal' => '<div style="width: expression(alert(\'XSS\'));">Test</div>',
                ],
                'expected' => [
                    'animal' => 'Test',
                ],
            ],
        ];
    }

    /**
     * @dataProvider htmlFieldSubmissionDataProvider
     */
    public function testHtmlReadOnlyFieldSubmission(string $submittedHtml, string $submittedEmail): void
    {
        // Create form with freehtml and email fields
        // this field is read-only so we want to assure that no data is stored from this field
        $formPayload = [
            'name'        => 'Submission test form',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Your story',
                    'type'         => 'freehtml',
                    'alias'        => 'your_story',
                    'properties'   => ['text' => ''],
                ],
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'leadField'    => 'email',
                    'mappedField'  => 'email',
                    'mappedObject' => 'contact',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        // Create the form
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $formId         = json_decode($clientResponse->getContent(), true)['form']['id'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode());

        // Submit the form directly via POST
        $this->client->request(
            Request::METHOD_POST,
            "/form/submit?formId={$formId}",
            [
                'mauticform' => [
                    'your_story' => $submittedHtml,
                    'email'      => $submittedEmail,
                    'formId'     => $formId,
                ],
            ]
        );

        // Verify submission
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $clientResponse  = $this->client->getResponse();
        $submissionsData = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('submissions', $submissionsData);
        $this->assertCount(1, $submissionsData['submissions']);

        // Verify submission results
        $submission = $submissionsData['submissions'][0];
        $this->assertArrayHasKey('results', $submission);

        // Verify that your_story is always false
        $this->assertArrayNotHasKey('your_story', $submission['results']);

        // Verify that email is stored correctly
        $this->assertArrayHasKey('email', $submission['results']);
        $this->assertSame($submittedEmail, $submission['results']['email']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function htmlFieldSubmissionDataProvider(): array
    {
        return [
            'any_text' => [
                '<div></div>',
                'test1@test.com',
            ],
            'with_content' => [
                '<div>Some content</div>',
                'test2@test.com',
            ],
        ];
    }
}
