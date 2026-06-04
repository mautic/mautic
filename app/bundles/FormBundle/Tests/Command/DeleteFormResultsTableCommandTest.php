<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Tests\FormTestHelperTrait;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteFormResultsTableCommandTest extends MauticMysqlTestCase
{
    use FormTestHelperTrait;

    protected $useCleanupRollback = false;

    public function testResultsTableAreDeletedIfFormsAreRemovedFromTable(): void
    {
        // In case any form results table exists whose form is deleted in previous test cases
        $this->deleteAllFormResultsTable();

        /** @var SubmissionRepository $submissionRepository */
        $submissionRepository = $this->em->getRepository(Submission::class);

        /** @var FormRepository $formRepository */
        $formRepository = $this->em->getRepository(Form::class);

        $deletedForms = 20;

        for ($i = 0; $i < $deletedForms; ++$i) {
            $payload = $this->getPayLoad();

            $form = $this->createFormViaApi($payload);

            $this->submitForm($form);

            $deleteForm = $formRepository->findOneBy(['id' => $form['id']]);
            $formRepository->deleteEntity($deleteForm);

            $deletedForm = $formRepository->findBy(['id' => $form['id']]);

            Assert::assertCount(0, $deletedForm);

            $submissions = $submissionRepository->findBy(['form' => $form['id']]);

            Assert::assertCount(0, $submissions);
        }

        $kernel      = self::$kernel;
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $command = $application->find('mautic:forms:delete-results-table');

        $commandTester = new CommandTester($command);
        $this->em->clear();

        $commandTester->execute([]);

        $this->assertEquals(0, $commandTester->getStatusCode());
        $outputMessage = $commandTester->getDisplay();
        $message       = "Dropped {$deletedForms} form results table whose forms have been deleted";

        $this->assertStringContainsString($message, $outputMessage);
    }
}
