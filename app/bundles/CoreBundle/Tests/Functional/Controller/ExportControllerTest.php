<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Entity\Report;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class ExportControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback   = false;
    protected bool $authenticateApi = true;

    public const PERMISSION_LEAD_EXPORT     = 'lead:export:enable';
    public const PERMISSION_FORM_EXPORT     = 'form:export:enable';
    public const PERMISSION_REPORT_EXPORT   = 'report:export:enable';

    public function testContactExportAction(): void
    {
        $permissions = [
            1024  => 'lead:export:enable',
            34    => 'lead:leads:create',
        ];
        $this->createAndLoginUser($permissions);

        $this->client->request(Request::METHOD_GET, '/s/contacts');
        $this->assertStringContainsString('Export to CSV', $this->client->getResponse()->getContent());
        $this->client->request(Request::METHOD_GET, '/s/contacts/batchExport?filetype=csv');
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testFormExportAction(): void
    {
        $permissions = [
            1024 => 'form:export:enable',
            38   => 'form:forms:create', // + viewown, viewother
        ];
        $this->createAndLoginUser($permissions);

        $formId = $this->createForm();

        $this->client->request(Request::METHOD_GET, '/s/forms/results/'.$formId);
        $this->assertStringContainsString('Export to CSV', $this->client->getResponse()->getContent());
        $this->client->request(Request::METHOD_GET, '/s/forms/results/'.$formId.'/export');
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testReportExportAction(): void
    {
        $permissions = [
            1024 => 'report:export:enable',
            34   => 'lead:leads:create',
            38   => 'report:reports:create', // + viewown, viewother
        ];
        $this->createAndLoginUser($permissions);

        $contact = new Lead();
        $contact->setDateAdded(new \DateTime());

        $this->em->persist($contact);
        $this->em->flush();

        $report = new Report();
        $report->setName('Contact report');
        $report->setSource('leads');
        $coulmns = [
            'l.id',
        ];
        $report->setColumns($coulmns);

        $this->getContainer()->get('mautic.report.model.report')->saveEntity($report);

        // Check the details page
        $this->client->request('GET', '/s/reports/view/'.$report->getId());
        $this->assertResponseIsSuccessful();

        $this->client->request(Request::METHOD_GET, '/s/reports/view/'.$report->getId().'');
        $this->assertStringContainsString('Export to CSV', $this->client->getResponse()->getContent());
        $this->client->request(Request::METHOD_GET, '/s/reports/view/'.$report->getId().'/export');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array<string|int> $permissions
     */
    private function createAndLoginUser(array $permissions): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create permissions to update user for the role
        foreach ($permissions as $bitwise => $permission) {
            $this->createPermission($permission, $role, $bitwise);
        }
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);
        $this->loginUser($user);

        return $user;
    }

    private function createPermission(string $rawPermission, Role $role, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);

        $this->em->persist($permission);
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }

    private function createForm(): int
    {
        $formPayload = [
            'name'        => 'Test Form',
            'formType'    => 'standalone',
            'description' => 'API test',
            'postAction'  => 'return',
            'fields'      => [
                [
                    'label'     => 'firstname',
                    'alias'     => 'firstname',
                    'type'      => 'text',
                ],
                [
                    'label'     => 'email',
                    'alias'     => 'email',
                    'type'      => 'email',
                    'leadField' => 'email',
                ],
                [
                    'label'     => 'description',
                    'alias'     => 'description',
                    'type'      => 'textarea',
                ],
                [
                    'label'          => 'checkbox',
                    'alias'          => 'checkbox',
                    'type'           => 'checkboxgrp',
                    'properties'     => [
                        'syncList'   => 0,
                        'optionlist' => [
                            'list'   => [
                                [
                                    'label' => 'val1',
                                    'value' => 'val1',
                                ],
                                [
                                    'label' => 'val2',
                                    'value' => 'val2',
                                ],
                                [
                                    'label' => 'val3',
                                    'value' => 'val3',
                                ],
                            ],
                        ],
                        'labelAttributes' => null,
                    ],
                ],
                [
                    'label'     => 'Submit',
                    'alias'     => 'submit',
                    'type'      => 'button',
                ],
            ],
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);

        return $response['form']['id'];
    }
}
