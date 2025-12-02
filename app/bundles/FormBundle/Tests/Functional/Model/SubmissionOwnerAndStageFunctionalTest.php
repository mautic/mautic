<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional\Model;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Submission;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubmissionOwnerAndStageFunctionalTest extends MauticMysqlTestCase
{
    private const STAGE_NAME_TOKEN       = '%stage_name%';
    private const SALES_USER_EMAIL_TOKEN = '%sales_user_email%';
    private const STAGE_NAME             = 'Test Stage';

    protected $useCleanupRollback   = false;

    /**
     * @param string[] $submissionDataPlaceholders
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws MappingException
     */
    #[DataProvider('ownerAndStageDataProvider')]
    public function testSubmissionSetsOwnerAndStage(
        string $testName,
        string $contactEmail,
        array $submissionDataPlaceholders,
        ?string $expectedOwnerUsername = null,
        ?string $expectedStageName = null,
    ): void {
        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $adminUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);

        $stage = new Stage();
        $stage->setName(self::STAGE_NAME);
        $this->em->persist($stage);
        $this->em->flush();
        $this->em->clear();

        $submissionData = $this->replacePlaceholders($submissionDataPlaceholders, [
            self::SALES_USER_EMAIL_TOKEN => $salesUser->getEmail(),
            '%sales_user_id%'            => $salesUser->getId(),
            '%admin_user_id%'            => (string) $adminUser->getId(),
            self::STAGE_NAME_TOKEN       => $stage->getName(),
        ]);

        $expectedOwnerId = null;
        if ($expectedOwnerUsername) {
            $expectedOwner   = $this->em->getRepository(User::class)->findOneBy(['username' => $expectedOwnerUsername]);
            $expectedOwnerId = $expectedOwner->getId();
        }

        $expectedStageId = null;
        if ($expectedStageName) {
            $expectedStage   = $this->em->getRepository(Stage::class)->findOneBy(['name' => $expectedStageName]);
            $expectedStageId = $expectedStage->getId();
        }

        $payload = [
            'name'        => 'Form test',
            'alias'       => 'formtest',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => $this->createFormFields(),
            'postAction'  => 'return',
        ];

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        $formId   = $response['form']['id'];

        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $form    = $crawler->filter('form[id=mauticform_formtest]')->form();

        $formValues = [];
        foreach ($submissionData as $key => $value) {
            $formValues['mauticform['.$key.']'] = $value;
        }

        $this->client->submit($form, $formValues);

        $submissions = $this->em->getRepository(Submission::class)->findBy(['form' => $formId]);
        $this->assertCount(1, $submissions, "Submission was not created for test: {$testName}");

        /** @var Submission $submission */
        $submission = $submissions[0];
        $contact    = $submission->getLead();

        $this->assertNotNull($contact, "Contact was not created for test: {$testName}");
        $this->assertSame($contactEmail, $contact->getEmail());

        if ($expectedOwnerId) {
            $this->assertNotNull($contact->getOwner(), "Owner not set for test: {$testName}");
            $this->assertSame($expectedOwnerId, $contact->getOwner()->getId(), "Incorrect owner set for test: {$testName}");
        } else {
            $this->assertNull($contact->getOwner(), "Owner was set unexpectedly for test: {$testName}");
        }

        if ($expectedStageId) {
            $this->assertNotNull($contact->getStage(), "Stage not set for test: {$testName}");
            $this->assertSame($expectedStageId, $contact->getStage()->getId(), "Incorrect stage set for test: {$testName}");
        } else {
            $this->assertNull($contact->getStage(), "Stage was set unexpectedly for test: {$testName}");
        }
    }

    /**
     * @return array<int, string[]>
     */
    private function createFormFields(): array
    {
        return [
            ['label' => 'Email', 'type' => 'email', 'alias' => 'email', 'leadField' => 'email'],
            ['label' => 'Owners Email', 'type' => 'text', 'alias' => 'owner_by_email', 'leadField' => 'ownerbyemail'],
            ['label' => 'Owners id', 'type' => 'text', 'alias' => 'owner_by_id', 'leadField' => 'ownerbyid'],
            ['label' => 'Stage', 'type' => 'text', 'alias' => 'stage', 'leadField' => 'stagebyname'],
            ['label' => 'Submit', 'type' => 'button'],
        ];
    }

    /**
     * @param array<string, string>          $data
     * @param array<string, int|string|null> $replacements
     *
     * @return array<string, string>
     */
    private function replacePlaceholders(array $data, array $replacements): array
    {
        return array_map(function ($value) use ($replacements) {
            return str_replace(array_keys($replacements), array_values($replacements), $value);
        }, $data);
    }

    /**
     * @param string[] $submissionDataPlaceholders
     *
     * @return array<string, array<string>|string|null>
     */
    private static function generateTestCase(
        string $testName,
        string $contactEmail,
        array $submissionDataPlaceholders,
        ?string $expectedOwnerUsername = null,
        ?string $expectedStageName = null,
    ): array {
        return [
            'testName'                   => $testName,
            'contactEmail'               => $contactEmail,
            'submissionDataPlaceholders' => $submissionDataPlaceholders,
            'expectedOwnerUsername'      => $expectedOwnerUsername,
            'expectedStageName'          => $expectedStageName,
        ];
    }

    /**
     * @return iterable<string, array<string, string|string[]|null>>
     */
    public static function ownerAndStageDataProvider(): iterable
    {
        yield 'owner by email' => self::generateTestCase(
            'owner by email',
            'contact.owner.email@test.com',
            [
                'email'          => 'contact.owner.email@test.com',
                'owner_by_email' => self::SALES_USER_EMAIL_TOKEN,
            ],
            'sales'
        );

        yield 'owner by id' => self::generateTestCase(
            'owner by id',
            'contact.owner.id@test.com',
            [
                'email'       => 'contact.owner.id@test.com',
                'owner_by_id' => '%sales_user_id%',
            ],
            'sales'
        );

        yield 'stage' => self::generateTestCase(
            'stage',
            'contact.stage.id@test.com',
            [
                'email' => 'contact.stage.id@test.com',
                'stage' => self::STAGE_NAME_TOKEN,
            ],
            null,
            self::STAGE_NAME
        );

        yield 'owner by email and stage' => self::generateTestCase(
            'owner by email and stage',
            'contact.owner.email.stage@test.com',
            [
                'email'          => 'contact.owner.email.stage@test.com',
                'owner_by_email' => self::SALES_USER_EMAIL_TOKEN,
                'stage'          => self::STAGE_NAME_TOKEN,
            ],
            'sales',
            self::STAGE_NAME
        );

        yield 'owner by id and stage' => self::generateTestCase(
            'owner by id and stage',
            'contact.owner.id.stage@test.com',
            [
                'email'       => 'contact.owner.id.stage@test.com',
                'owner_by_id' => '%sales_user_id%',
                'stage'       => self::STAGE_NAME_TOKEN,
            ],
            'sales',
            self::STAGE_NAME
        );

        yield 'owner by email and id (email has precedence)' => self::generateTestCase(
            'owner by email and id',
            'contact.owner.email.id@test.com',
            [
                'email'          => 'contact.owner.email.id@test.com',
                'owner_by_email' => self::SALES_USER_EMAIL_TOKEN,
                'owner_by_id'    => '%admin_user_id%',
            ],
            'sales'
        );

        yield 'invalid owner email' => self::generateTestCase(
            'invalid owner email',
            'contact.invalid.owner.email@test.com',
            [
                'email'          => 'contact.invalid.owner.email@test.com',
                'owner_by_email' => 'nonexistent@email.com',
            ]
        );

        yield 'invalid owner id' => self::generateTestCase(
            'invalid owner id',
            'contact.invalid.owner.id@test.com',
            [
                'email'       => 'contact.invalid.owner.id@test.com',
                'owner_by_id' => '99999',
            ]
        );

        yield 'invalid stage name' => self::generateTestCase(
            'invalid stage name',
            'contact.invalid.stage.id@test.com',
            [
                'email' => 'contact.invalid.stage.id@test.com',
                'stage' => 'mautic',
            ]
        );

        yield 'empty owner and stage' => self::generateTestCase(
            'empty owner and stage',
            'contact.empty.fields@test.com',
            [
                'email'          => 'contact.empty.fields@test.com',
                'owner_by_email' => '',
                'stage'          => '',
            ]
        );
    }
}
