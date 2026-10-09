<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;

class EmailDefaultsHelper
{
    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PageModel $pageModel,
    ) {
    }

    /**
     * Applies config-based defaults that should be materialized on the email.
     * Preserves the entity's existing changes array so defaults don't appear
     * as user edits in the audit log.
     */
    public function applyDefaults(Email $email): void
    {
        $changesBefore = $email->getChanges();

        $this->applyUtmTagDefaults($email);

        // Restore only the changes that existed before defaults were applied,
        // so system-applied defaults don't appear as user edits in the audit log.
        $email->setChanges($changesBefore);
    }

    public function resolvePreferenceCenter(Email $email, ?Lead $lead = null, ?Request $request = null): ?Page
    {
        $preferenceCenter = $email->getPreferenceCenter();
        if ($preferenceCenter instanceof Page && $preferenceCenter->getIsPreferenceCenter()) {
            return $this->resolveTranslation($preferenceCenter, $lead, $request);
        }

        $defaultId = $this->coreParametersHelper->get('email_default_preference_center_id');
        $page      = null;

        if (!empty($defaultId)) {
            $candidate = $this->entityManager->find(Page::class, $defaultId);
            if ($candidate instanceof Page && $candidate->getIsPreferenceCenter() && $candidate->isPublished()) {
                $page = $candidate;
            }
        }

        return $page !== null ? $this->resolveTranslation($page, $lead, $request) : null;
    }

    private function resolveTranslation(Page $page, ?Lead $lead, ?Request $request): Page
    {
        [, $translated] = $this->pageModel->getTranslatedEntity($page, $lead, $request);

        return $translated instanceof Page ? $translated : $page;
    }

    private function applyUtmTagDefaults(Email $email): void
    {
        $existingTags = array_filter($email->getUtmTags(), static fn ($tag): bool => null !== $tag && '' !== $tag);
        if ([] !== $existingTags) {
            return;
        }

        $utmTags = [
            'utmSource'   => $this->coreParametersHelper->get('email_default_utm_source'),
            'utmMedium'   => $this->coreParametersHelper->get('email_default_utm_medium'),
            'utmCampaign' => $this->coreParametersHelper->get('email_default_utm_campaign'),
            'utmContent'  => $this->coreParametersHelper->get('email_default_utm_content'),
        ];

        $filtered = array_filter($utmTags, static fn ($tag): bool => null !== $tag && '' !== $tag);
        if ([] !== $filtered) {
            $email->setUtmTags($filtered);
        }
    }
}
