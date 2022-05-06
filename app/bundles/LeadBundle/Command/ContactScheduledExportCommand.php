<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use Mautic\LeadBundle\Model\ContactExportSchedulerModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactScheduledExportCommand extends Command
{
    public const COMMAND_NAME = 'mautic:contacts:scheduled_export';

    private ContactExportSchedulerModel $contactExportSchedulerModel;
    private ExportHelper $exportHelper;
    private LeadModel $leadModel;
    private TranslatorInterface $translator;

    public function __construct(
        ContactExportSchedulerModel $contactExportSchedulerModel,
        ExportHelper $exportHelper,
        LeadModel $leadModel,
        TranslatorInterface $translator
    ) {
        $this->contactExportSchedulerModel = $contactExportSchedulerModel;
        $this->exportHelper                = $exportHelper;
        $this->leadModel                   = $leadModel;
        $this->translator                  = $translator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Export contacts which are scheduled in `contact_export_scheduler` table.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while ($contactExportScheduler = $this->contactExportSchedulerModel->getRepository()
            ->findOneBy([], ['id' => 'ASC'])) {
            $data              = $contactExportScheduler->getData();
            $fileType          = $data['fileType'];
            $resultsCallback   = function ($contact) {
                return $contact->getProfileFields();
            };
            $iterator          = new IteratorExportDataModel(
                $this->leadModel,
                $contactExportScheduler->getData(),
                $resultsCallback
            );
            $scheduledDateTime = $contactExportScheduler->getScheduledDateTime();
            \assert($scheduledDateTime instanceof \DateTimeImmutable);
            $fileName = 'contacts_exported_'.$scheduledDateTime->format('Y_m_d_H_i_s');
            $filePath = $this->exportResultsAs($iterator, $fileType, $fileName);

            // @todo email $filePath

            $this->contactExportSchedulerModel->deleteEntity($contactExportScheduler);
        }

        return ExitCode::SUCCESS;
    }

    public function exportResultsAs(IteratorExportDataModel $iterator, string $fileType, string $fileName): string
    {
        if (!in_array($fileType, $this->exportHelper->getSupportedExportTypes(), true)) {
            throw new BadRequestHttpException($this->translator->trans('mautic.error.invalid.export.type', ['%type%' => $fileType]));
        }

        return $this->exportHelper->exportDataIntoFile($iterator, $fileType, strtolower($fileName.'.'.$fileType));
    }
}
