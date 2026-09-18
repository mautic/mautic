<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Helper\FakeContactHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailPreviewHelper
{
    public function __construct(
        private LeadRepository $leadRepository,
        private CompanyRepository $companyRepository,
        private CompanyLeadRepository $companyLeadRepositoryfieldList,
        private EmailModel $emailModel,
        private EventDispatcherInterface $dispatcher,
        private FakeContactHelper $fakeContactHelper,
    ) {
    }

    /**
     * @param array<int, string> $contact
     * @param array<int, string> $companies
     */
    public function generatePreviewContent(Email $email, array $contact, array $companies, string $content): string
    {
        // bogus ID
        $idHash = 'xxxxxxxxxxxxxx';

        // Override tracking_pixel
        $tokens = ['{tracking_pixel}' => ''];

        // Prepare contact
        if ([] === $contact) {
            $contact = $this->fakeContactHelper->prepareFakeContactWithPrimaryCompany();
        }

        // Prepare company
        if ([] === $companies && $contact['id']) {
            $companies = array_filter(
                array_map(
                    fn (string $id): array => $this->companyRepository->getCompanies(false, $id)[0] ?? [],
                    $this->companyLeadRepositoryfieldList->getCompanyIdsByLeadId($contact['id'])
                )
            );
        }

        if ([] === $companies) {
            $companies[] = $this->fakeContactHelper->prepareFakeContactWithPrimaryCompany();
        }

        $contact['companies'] = $companies;

        // Generate and replace tokens
        $event = new EmailSendEvent(
            null,
            [
                'content'      => $content,
                'email'        => $email,
                'idHash'       => $idHash,
                'tokens'       => $tokens,
                'internalSend' => true,
                'lead'         => $contact,
            ]
        );
        $this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_DISPLAY);

        return $event->getContent(true);
    }

    /**
     * @param array<mixed> $contact
     * @param array<mixed> $company
     */
    public function generateDownloadFileName(array $contact, array $company, string $name, string $fileType): string
    {
        $fileName = $name ?: 'EmailPreview';
        $prefix   = null;

        if (!empty($company)) {
            $prefix = $company[0]['companyname'] ?? '';
        } elseif ($contact) {
            $prefix = $contact['firstname'] ?? '';
        }

        if ($prefix) {
            $fileName = $prefix.'-'.$fileName;
        }

        return $fileName.'.'.$fileType;
    }

    /**
     * @return array<mixed> $contact
     */
    public function getContactEntity(int $contactId): array
    {
        if (!$contactId) {
            return [];
        }

        $contact = $this->leadRepository->getLead($contactId);

        return $this->emailModel->enrichedContactWithCompanies($contact);
    }

    /**
     * @return array<mixed> $compmany
     */
    public function getCompanyEntity(int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        return $this->companyRepository->getCompanies(false, (string) $companyId);
    }
}
