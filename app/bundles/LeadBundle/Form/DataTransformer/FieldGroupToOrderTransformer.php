<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\FieldGroupRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<FieldGroup|null, int|null>
 */
class FieldGroupToOrderTransformer implements DataTransformerInterface
{
    public function __construct(
        private FieldGroupRepository $fieldGroupRepository,
    ) {
    }

    /**
     * Entity order (int) -> the group this one currently appears *before*
     * (the next group in the order). Null when this group is last, which the
     * dropdown renders as the "Choose one..." (place last) placeholder.
     *
     * Orders are kept contiguous (1..n) by FieldGroupModel::reorderGroupsByEntity,
     * so the next group is reliably found at order + 1.
     */
    public function transform(mixed $order): mixed
    {
        if (!$order) {
            return null;
        }

        return $this->fieldGroupRepository->findOneBy(['order' => $order + 1]);
    }

    /**
     * Selected "appear before" group -> its order (the target slot). Null (no
     * selection) means place last; FieldGroupModel appends in that case.
     */
    public function reverseTransform(mixed $group): mixed
    {
        if (null === $group) {
            return null;
        }

        return $group->getOrder();
    }
}
