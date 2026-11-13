<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockDomainSubmissionsFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

    protected $useCleanupRollback   = false;

    protected bool $authenticateApi = true;

    protected function setUp(): void
    {
        $this->configParams['do_not_submit_emails'] = [];
        parent::setUp();
    }

    public function testEmailFieldRepairYieldsBoundedBatches(): void
    {
        $form = new Form();
        $form->setName('Batch repair');
        $form->setAlias('batch_repair');
        $form->setFormType('standalone');
        $this->em->persist($form);

        for ($index = 0; $index < 501; ++$index) {
            $field = new Field();
            $field->setForm($form);
            $field->setType('email');
            $field->setLabel('Email '.$index);
            $field->setAlias('email_'.$index);
            $field->setValidation(['blockfreeemail' => true]);
            $this->em->persist($field);
        }
        $this->em->flush();

        $model   = self::getContainer()->get(FormModel::class);
        $batches = $model->findEmailFieldsWithMissingDonotSubmitValidation();
        $this->assertCount(500, $batches->current());
        $this->assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' WHERE form_id = ? AND JSON_CONTAINS_PATH(validation, ?, ?) = 0',
            [$form->getId(), 'one', '$.donotsubmit']
        ));

        $batches->next();
        $this->assertCount(1, $batches->current());
        $batches->next();
        $this->assertFalse($batches->valid());
        $this->assertSame([], iterator_to_array($model->findEmailFieldsWithMissingDonotSubmitValidation()));
        $this->assertSame(501, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' WHERE form_id = ? AND JSON_EXTRACT(validation, ?) = true AND JSON_EXTRACT(validation, ?) = true',
            [$form->getId(), '$.donotsubmit', '$.blockfreeemail']
        ));
    }

    public function testMarkSpamBlocksDomainForFutureSubmissions(): void
    {
        $payload = [
            'name'        => 'Block domain spam test form',
            'description' => 'Form created via block domain submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'     => 'Email',
                    'type'      => 'email',
                    'alias'     => 'email',
                    'leadField' => 'email',
                    'validation' => ['blockfreeemail' => true],
                ],
                [
                    'label'      => 'Optional email',
                    'type'       => 'email',
                    'alias'      => 'optional_email',
                    'validation' => ['donotsubmit' => false],
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

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id^="mauticform_"]');
        $form        = $formCrawler->form();

        $form->setValues([
            'mauticform[email]' => 'blocked@example.com',
        ]);

        $this->client->submit($form);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions);

        /** @var Submission $submission */
        $submission = $submissions[0];
        $batchIds   = urlencode(json_encode([$submission->getId()], JSON_THROW_ON_ERROR));

        foreach ([
            "/s/forms/results/{$formId}/markSpam/{$submission->getId()}",
            "/s/forms/results/{$formId}/batchMarkSpam?ids={$batchIds}",
        ] as $url) {
            $this->client->request(Request::METHOD_POST, $url);
            $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        }

        $editor = $this->createUserWithPermission([
            'email'      => 'form-editor@mautic-test.com',
            'user-name'  => 'form-editor',
            'first-name' => 'Form',
            'last-name'  => 'Editor',
            'role'       => [
                'name'        => 'form-editor-role',
                'permissions' => ['form:forms' => 1023],
            ],
        ]);
        $this->em->flush();
        $checkedOut = new \DateTime('now');
        $this->connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.Form::TABLE_NAME.' SET checked_out = ?, checked_out_by = ?, checked_out_by_user = ? WHERE id = ?',
            [$checkedOut->format('Y-m-d H:i:s'), $editor->getId(), $editor->getName(), $formId]
        );

        $this->client->setServerParameter('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}?tmpl=list");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->setCsrfHeader();
        $this->client->request(
            Request::METHOD_POST,
            "/s/forms/results/{$formId}/markSpam/{$submission->getId()}"
        );

        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $validation = json_decode($this->connection->fetchOne(
            'SELECT validation FROM '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' WHERE form_id = ? AND alias = ?',
            [$formId, 'email']
        ), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue((bool) $validation['blockfreeemail']);
        $this->assertTrue((bool) $validation['donotsubmit']);
        $optionalValidation = json_decode($this->connection->fetchOne(
            'SELECT validation FROM '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' WHERE form_id = ? AND alias = ?',
            [$formId, 'optional_email']
        ), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse((bool) $optionalValidation['donotsubmit']);

        $this->connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' SET validation = ? WHERE form_id = ? AND alias = ?',
            ['[]', $formId, 'email']
        );

        $this->connection->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.Form::TABLE_NAME.' SET cached_html = ? WHERE id = ?',
            ['stale', $formId]
        );

        $this->client->request(
            Request::METHOD_POST,
            "/s/forms/results/{$formId}/batchMarkSpam?ids={$batchIds}"
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $repairedValidation = json_decode($this->connection->fetchOne(
            'SELECT validation FROM '.MAUTIC_TABLE_PREFIX.Field::TABLE_NAME.' WHERE form_id = ? AND alias = ?',
            [$formId, 'email']
        ), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue((bool) $repairedValidation['donotsubmit']);
        $this->assertSame('', $this->connection->fetchOne(
            'SELECT cached_html FROM '.MAUTIC_TABLE_PREFIX.Form::TABLE_NAME.' WHERE id = ?',
            [$formId]
        ));

        $lock = $this->connection->fetchAssociative(
            'SELECT checked_out, checked_out_by FROM '.MAUTIC_TABLE_PREFIX.Form::TABLE_NAME.' WHERE id = ?',
            [$formId]
        );
        $this->assertSame($checkedOut->format('Y-m-d H:i:s'), $lock['checked_out']);
        $this->assertSame($editor->getId(), (int) $lock['checked_out_by']);

        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('form[id^="mauticform_"]'));
        $this->assertNotSame('', $this->connection->fetchOne(
            'SELECT cached_html FROM '.MAUTIC_TABLE_PREFIX.Form::TABLE_NAME.' WHERE id = ?',
            [$formId]
        ));

        $this->loginOtherUser($editor);
        $this->client->setServerParameter('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}?tmpl=list");
        $this->setCsrfHeader();

        foreach ([
            "/s/forms/results/{$formId}/markSpam/{$submission->getId()}",
            "/s/forms/results/{$formId}/batchMarkSpam?ids={$batchIds}",
        ] as $url) {
            $this->client->request(Request::METHOD_POST, $url);
            $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        }

        unset($this->configParams['do_not_submit_emails']);
        $this->setUpSymfony($this->configParams);

        $this->client->request(
            Request::METHOD_POST,
            "/form/submit?formId={$formId}&ajax=1",
            [
                'mauticform' => [
                    'email'    => 'another@example.com',
                    'formId'   => $formId,
                    'formName' => 'blockdomainspamtestform',
                ],
            ]
        );
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame(0, $response['success']);
        $this->assertSame('Cannot be sent with this email', $response['validationErrors']['email']);
    }
}
