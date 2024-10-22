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
    protected $useCleanupRollback = false;

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

        $this->assertSame(1, $formCrawler->count());

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
        $this->assertSame(1, $formCrawler->count());
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

        Assert::assertSame(null, $contact->getCountry());
        Assert::assertSame(null, $contact->getState());

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
        $this->assertSame(1, $formCrawler->count());
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
        $this->assertSame(1, $formCrawler->count());
        // show just one text field
        $this->assertSame(1, $formCrawler->filter('.mauticform-text')->count());
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
        $this->assertSame(1, $formCrawler->count());
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
            $this->connection->executeQuery("DROP TABLE {$tablePrefix}form_results_1_submission");
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
        $this->assertSame(1, $formCrawler->count());
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
        $this->assertSame(1, $formCrawler->count());
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
        $this->assertCount(1, $formCrawler);
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
        return 'test-pass';
    }
}
