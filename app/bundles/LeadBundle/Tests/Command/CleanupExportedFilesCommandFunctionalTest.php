<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\CleanupExportedFilesCommand;
use Mautic\LeadBundle\Command\ContactScheduledExportCommand;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('database')]
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
        $this->assertFileDoesNotExist($filePath);
    }

    private function exportContactToCsvFile(): string
    {
        $this->createContacts();
        $this->client->request(
            Request::METHOD_POST,
            's/contacts/batchExport',
            ['filetype' => 'csv']
        );
        self::assertResponseIsSuccessful();
        $contactExportSchedulerRows = $this->checkContactExportScheduler(1);
        /** @var ContactExportScheduler $contactExportScheduler */
        $contactExportScheduler     = $contactExportSchedulerRows[0];
        $this->testSymfonyCommand(ContactScheduledExportCommand::COMMAND_NAME, ['--ids' => $contactExportScheduler->getId()]);

        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper    = self::getContainer()->get(CoreParametersHelper::class);
        $zipFileName             = 'contacts_export_'.$contactExportScheduler->getScheduledDateTime()
                ->format('Y_m_d_H_i_s').'.zip';
        $filePath = $coreParametersHelper->get('contact_export_dir').'/'.$zipFileName;
        $this->assertFileExists($filePath);

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

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get(LeadModel::class);
        $leadModel->saveEntities($contacts);
    }

    /**
     * @return array<mixed>
     */
    private function checkContactExportScheduler(int $count): array
    {
        $repo    = $this->em->getRepository(ContactExportScheduler::class);
        $allRows = $repo->findAll();
        $this->assertCount($count, $allRows);

        return $allRows;
    }
}
