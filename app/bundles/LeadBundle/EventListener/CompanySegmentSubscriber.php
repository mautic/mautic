<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\CompanySegmentPreDelete;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanySegmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompanySegmentModel $companySegmentModel,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::COMPANY_SEGMENT_PRE_DELETE => ['onCompanySegmentPreDelete', 0],
        ];
    }

    public function onCompanySegmentPreDelete(CompanySegmentPreDelete $event): void
    {
        $companySegment = $event->getCompanySegment();
        $id             = $companySegment->getId();

        if (null === $id) {
            return;
        }

        // Check for dependencies in other company segments
        $dependentCompanySegments = $this->companySegmentModel->getSegmentsWithDependenciesOnSegment($id, 'name');
        if ([] !== $dependentCompanySegments) {
            $message = $this->translator->trans(
                'mautic.company_segments.is_in_use.delete',
                [
                    '%segments%'            => implode(', ', $dependentCompanySegments),
                    '%companySegmentName%' => $companySegment->getName(),
                ],
                'validators'
            );
            $event->addDependencyError($message);
        }

        // Check for dependencies in contact segments
        $dependentContactSegments = $this->companySegmentModel->getSegmentsWithDependenciesOnSegment($id, 'name', true);
        if ([] !== $dependentContactSegments) {
            $message = $this->translator->trans(
                'mautic.company_segments.is_in_use.delete_contact_segments',
                [
                    '%segments%'            => implode(', ', $dependentContactSegments),
                    '%companySegmentName%' => $companySegment->getName(),
                ],
                'validators'
            );
            $event->addDependencyError($message);
        }
    }
}
