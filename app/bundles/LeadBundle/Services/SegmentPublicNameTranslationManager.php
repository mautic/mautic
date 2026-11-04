<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListTranslation;
use Mautic\LeadBundle\Entity\LeadListTranslationRepository;
use Mautic\LeadBundle\Helper\SegmentPublicNameLocaleNormalizer;

final class SegmentPublicNameTranslationManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LeadListTranslationRepository $translationRepository,
        private readonly SegmentPublicNameLocaleNormalizer $localeNormalizer,
    ) {
    }

    /**
     * Replace the translations for a persisted, public Segment. The caller flushes the changes.
     *
     * @param list<array{locale: string, publicName: string}> $translations
     */
    public function sync(LeadList $segment, array $translations): void
    {
        if (!$segment->getIsPreferenceCenter()) {
            throw new \LogicException('Translations can only be edited for Preference Center segments.');
        }
        if (null === $segment->getId()) {
            throw new \LogicException('The segment must be persisted before editing translations.');
        }

        $requested = [];
        foreach ($translations as $translation) {
            $locale     = $this->localeNormalizer->normalize($translation['locale']);
            $publicName = trim($translation['publicName']);
            if ('' === $publicName || mb_strlen($publicName) > 255) {
                throw new \InvalidArgumentException('A translated public name must contain 1 to 255 characters.');
            }
            if (array_key_exists($locale, $requested)) {
                throw new \InvalidArgumentException('Each locale may appear only once per segment.');
            }
            $requested[$locale] = $publicName;
        }

        foreach ($this->translationRepository->findBy(['leadList' => $segment]) as $stored) {
            $locale = $stored->getLocale();
            if (!array_key_exists($locale, $requested)) {
                $this->entityManager->remove($stored);
                continue;
            }

            $stored->setPublicName($requested[$locale]);
            $this->entityManager->persist($stored);
            unset($requested[$locale]);
        }

        foreach ($requested as $locale => $publicName) {
            $this->entityManager->persist(new LeadListTranslation($segment, $locale, $publicName));
        }
    }
}
