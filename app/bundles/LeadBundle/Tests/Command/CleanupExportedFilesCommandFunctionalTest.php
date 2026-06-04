<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\CleanupExportedFilesCommand;
use Mautic\LeadBundle\Command\ContactScheduledExportCommand;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class CleanupExportedFilesCommandFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['clear_export_files_after_days'] = 0;
        $this->configParams['contact_export_dir']            = '/tmp';

        parent::setUp();
    }

    /**
     * @throws \Exception
     */
    public function testCleanupContactExportFiles(): void
    {
        $filePath = $this->exportContactToCsvFile();

        $this->testSymfonyCommand(CleanupExportedFilesCommand::COMMAND_NAME);
        Assert::assertFileDoesNotExist($filePath);
    }

    private function exportContactToCsvFile(): string
    {
        $this->createContacts();
        $this->client->request(
            Request::METHOD_POST,
            's/contacts/batchExport',
            ['filetype' => 'csv']
        );
        Assert::assertTrue($this->client->getResponse()->isOk());
        $contactExportSchedulerRows = $this->checkContactExportScheduler(1);
        /** @var ContactExportScheduler $contactExportScheduler */
        $contactExportScheduler     = $contactExportSchedulerRows[0];
        $this->testSymfonyCommand(ContactScheduledExportCommand::COMMAND_NAME, ['--ids' => $contactExportScheduler->getId()]);

        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper    = self::getContainer()->get('mautic.helper.core_parameters');
        $zipFileName             = 'contacts_export_'.$contactExportScheduler->getScheduledDateTime()
                ->format('Y_m_d_H_i_s').'.zip';
        $filePath = $coreParametersHelper->get('contact_export_dir').'/'.$zipFileName;
        Assert::assertFileExists($filePath);

        return $filePath;
    }

    private function createContacts(): void
    {
        $contacts = [];

        for ($i = 1; $i <= 2; ++$i) {
            $contact = new Lead();
            $contact
                ->setFirstname('ContactFirst'.$i)
                ->setLastname('ContactLast'.$i)
                ->setEmail('FirstLast'.$i.'@email.com');
            $contacts[] = $contact;
        }

        $leadModel = self::getContainer()->get('mautic.lead.model.lead');
        $leadModel->saveEntities($contacts);
    }

    /**
     * @return array<mixed>
     */
    private function checkContactExportScheduler(int $count): array
    {
        $repo    = $this->em->getRepository(ContactExportScheduler::class);
        $allRows = $repo->findAll();
        Assert::assertCount($count, $allRows);

        return $allRows;
    }
}
