<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

final class SubmissionFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

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
                    'label'        => 'State',
                    'type'         => 'select',
                    'alias'        => 'state',
                    'leadField'    => 'state',
                    'parent'       => $response['form']['fields'][0]['id'],
                    'isRequired'   => true,
                    'conditions'   => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties'   => [
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
        $this->setUpSymfony(
            [
                'api_enabled'           => true,
                'api_enable_basic_auth' => true,
            ]
        );
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
                    'label'        => 'State',
                    'type'         => 'select',
                    'alias'        => 'state',
                    'leadField'    => 'state',
                    'parent'       => $response['form']['fields'][0]['id'],
                    'isRequired'   => true,
                    'conditions'   => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties'   => [
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
        $this->setUpSymfony(
            [
                'api_enabled'           => true,
                'api_enable_basic_auth' => true,
            ]
        );

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
                    'label'        => 'State',
                    'type'         => 'select',
                    'alias'        => 'state',
                    'leadField'    => 'state',
                    'parent'       => $response['form']['fields'][0]['id'],
                    'isRequired'   => true,
                    'conditions'   => [
                        'expr'   => 'in',
                        'any'    => 0,
                        'values' => ['Australia'],
                    ],
                    'properties'   => [
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
        $this->setUpSymfony(
            [
                'api_enabled'           => true,
                'api_enable_basic_auth' => true,
            ]
        );

        // Cleanup:
        $this->client->request(Request::METHOD_DELETE, "/api/forms/{$formId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    protected function tearDown(): void
    {
        $tablePrefix = self::$container->getParameter('mautic.db_table_prefix');

        parent::tearDown();

        if ($this->connection->getSchemaManager()->tablesExist("{$tablePrefix}form_results_1_submission")) {
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

        // The previous request changes user to anonymous. We have to configure API again.
        $this->setUpSymfony(
            [
                'api_enabled'           => true,
                'api_enable_basic_auth' => true,
            ]
        );

        // fetch form submissions as Admin User
        $this->client->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $submission     = $response['submissions'][0];

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertSame($formId, $submission['form']['id']);
        Assert::assertGreaterThanOrEqual(1, $response['total']);

        // Create non admin user
        $this->createUser();

        // Fetch form submissions as non-admin-user who don't have the permission to view submissions
        $apiClient = $this->createAnotherClient('non-admin-user', 'test-pass');
        $apiClient->followRedirects();
        $apiClient->request(Request::METHOD_GET, "/api/forms/{$formId}/submissions");
        $clientResponse = $apiClient->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    private function createUser(): void
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

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('test-pass', $user->getSalt()));

        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        $userRepo->saveEntities([$user]);
    }
}
