<?php

namespace Mautic\CoreBundle\Model\AbTest;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\VariantEntityInterface;

class VariantUpdaterService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * VariantUpdaterService constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param VariantEntityInterface $parent
     */
    public function updateVariantChildren(VariantEntityInterface $entity)
    {
        $changes = $entity->getChanges();

        if (!array_key_exists('variantSettings', $changes)) {
            return;
        }

        if ($entity->getVariantParent()) {
            $this->updateChildVariant($entity);
        } else {
            $this->updateVariantChildrenFromParent($entity);
        }
    }

    /**
     * Updates winnerCriteria for children variants to keep BC compatibility.
     *
     * @param array $parentSettings
     */
    private function updateVariantSettingsFromParent(VariantEntityInterface $variant, $parentSettings)
    {
        $variantSettings                   = $variant->getVariantSettings();
        $variantSettings['winnerCriteria'] = $parentSettings['winnerCriteria'];
        $variant->setVariantSettings($variantSettings);
    }

    private function updateChildVariant(VariantEntityInterface $entity)
    {
        $this->updateVariantSettingsFromParent($entity, $entity->getVariantParent()->getVariantSettings());
    }

    private function updateVariantChildrenFromParent(VariantEntityInterface $entity)
    {
        list($parent, $children) = $entity->getVariants();
        if (empty($children)) {
            return;
        }

        $parentSettings = $parent->getVariantSettings();

        foreach ($children as $variant) {
            // We want to keep variants settings in children variants for BC compatibility
            $this->updateVariantSettingsFromParent($variant, $parentSettings);
            $this->em->getRepository(get_class($variant))->saveEntity($variant);
        }
    }
}
