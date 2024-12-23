<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Model\IteratorExportDataModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\ContactExportSchedulerRepository;
use Mautic\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCommonModel<ContactExportScheduler>
 */
class ContactExportSchedulerModel extends AbstractCommonModel
{
    private const EXPORT_FILE_NAME_DATE_FORMAT = 'Y_m_d_H_i_s';

    public function __construct(
        private RequestStack $requestStack,
        private LeadModel $leadModel,
        private ExportHelper $exportHelper,
        private MailHelper $mailHelper,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): ContactExportSchedulerRepository
    {
        /** @var ContactExportSchedulerRepository $repo */
        $repo = $this->em->getRepository(ContactExportScheduler::class);

        return $repo;
    }

    /**
     * @param array<mixed> $permissions
     *
     * @return array<string, mixed>
     */
    public function prepareData(array $permissions): array
    {
        $search     = $this->requestStack->getSession()->get('mautic.lead.filter', '');
        $orderBy    = $this->requestStack->getSession()->get('mautic.lead.orderby', 'l.last_active');
        $orderByDir = $this->requestStack->getSession()->get('mautic.lead.orderbydir', 'DESC');
        $indexMode  = $this->requestStack->getSession()->get('mautic.lead.indexmode', 'list');

        $anonymous = $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous');

        /** @var Request $request */
        $request = $this->getRequest();

        $ids      = $request->get('ids');
        $fileType = $request->get('filetype', 'csv');

        $filter = ['string' => $search, 'force' => []];

        if (!empty($ids)) {
            $filter['force'] = [
                [
                    'column' => 'l.id',
                    'expr'   => 'in',
                    'value'  => json_decode($ids, true, 512, JSON_THROW_ON_ERROR),
                ],
            ];
        } else {
            if ('list' !== $indexMode || (!str_contains($search, $anonymous))) {
                // Remove anonymous leads unless requested to prevent clutter.
                $filter['force'] = [
                    [
                        'column' => 'l.dateIdentified',
                        'expr'   => 'isNotNull',
                    ],
                ];
            }

            if (!$permissions['lead:leads:viewother']) {
                // Show only owner's contacts.
                $filter['force'] = [
                    [
                        'column' => 'l.owner',
                        'expr'   => 'eq',
                    ],
                ];
            }
        }

        return [
            'start'          => 0,
            'limit'          => $this->coreParametersHelper->get('contact_export_batch_size', 1000),
            'filter'         => $filter,
            'orderBy'        => $orderBy,
            'orderByDir'     => $orderByDir,
            'withTotalCount' => true,
            'fileType'       => $fileType,
        ];
    }

    /**
     * @param array<mixed> $data
     */
    public function saveEntity(array $data): ContactExportScheduler
    {
        $contactExportScheduler = new ContactExportScheduler();
        $contactExportScheduler
            ->setUser($this->userHelper->getUser())
            ->setScheduledDateTime(new \DateTimeImmutable())
            ->setData($data);

        $this->em->persist($contactExportScheduler);
        $this->em->flush();

        return $contactExportScheduler;
    }

    public function processAndGetExportFilePath(ContactExportScheduler $contactExportScheduler): string
    {
        $data            = $contactExportScheduler->getData();
        $fileType        = $data['fileType'];
        $resultsCallback = fn ($contact) => $contact->getProfileFields();
        $iterator        = new IteratorExportDataModel(
            $this->leadModel,
            $contactExportScheduler->getData(),
            $resultsCallback,
            true
        );
        /** @var \DateTimeImmutable $scheduledDateTime */
        $scheduledDateTime = $contactExportScheduler->getScheduledDateTime();
        $fileName          = 'contacts_export_'.$scheduledDateTime->format(self::EXPORT_FILE_NAME_DATE_FORMAT);

        return $this->exportResultsAs($iterator, $fileType, $fileName);
    }

    public function getEmailMessageWithLink(string $filePath): string
    {
        $link = $this->router->generate(
            'mautic_contact_export_download',
            ['fileName' => basename($filePath)],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->translator->trans(
            'mautic.lead.export.email',
            ['%link%' => $link, '%label%' => basename($filePath)]
        );
    }

    public function sendEmail(ContactExportScheduler $contactExportScheduler, string $filePath): void
    {
        /** @var User $user */
        $user    = $contactExportScheduler->getUser();
        $message = $this->getEmailMessageWithLink($filePath);

        $this->mailHelper->setTo([$user->getEmail() => $user->getName()]);
        $this->mailHelper->setSubject(
            $this->translator->trans('mautic.lead.export.email_subject', ['%file_name%' => basename($filePath)])
        );
        $this->mailHelper->setBody($message);
        $this->mailHelper->parsePlainText($message);
        $this->mailHelper->send(true);
    }

    public function deleteEntity(ContactExportScheduler $contactExportScheduler): void
    {
        $this->em->remove($contactExportScheduler);
        $this->em->flush();
    }

    public function getExportFileToDownload(string $fileName): BinaryFileResponse
    {
        $filePath    = $this->coreParametersHelper->get('contact_export_dir').'/'.$fileName;
        $contentType = $this->getContactExportFileContentType($fileName);

        return new BinaryFileResponse(
            $filePath,
            Response::HTTP_OK,
            [
                'Content-Type'        => $contentType,
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Expires'             => 0,
                'Cache-Control'       => 'must-revalidate',
                'Pragma'              => 'public',
            ]
        );
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function exportResultsAs(IteratorExportDataModel $iterator, string $fileType, string $fileName): string
    {
        if (!in_array($fileType, $this->exportHelper->getSupportedExportTypes(), true)) {
            throw new BadRequestHttpException($this->translator->trans('mautic.error.invalid.export.type', ['%type%' => $fileType]));
        }

        $csvFilePath = $this->exportHelper
            ->exportDataIntoFile($iterator, $fileType, strtolower($fileName.'.'.$fileType));

        return $this->exportHelper->zipFile($csvFilePath, 'contacts_export.csv');
    }

    private function getContactExportFileContentType(string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if ('zip' === $ext) {
            return 'application/zip';
        }

        throw new BadRequestHttpException($this->translator->trans('mautic.error.invalid.specific.export.type', ['%type%' => $ext, '%expected_type%' => 'zip']));
    }
}
