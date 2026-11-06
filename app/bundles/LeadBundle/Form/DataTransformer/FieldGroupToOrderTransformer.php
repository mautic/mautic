<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\FieldGroupRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Maps the model value (an integer order) to/from the group entity shown in the
 * "Group order" dropdown.
 *
 * @implements DataTransformerInterface<int|null, FieldGroup|null>
 */
final readonly class FieldGroupToOrderTransformer implements DataTransformerInterface
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
     * Uses a "first group with order > current" query so that gaps caused by
     * deletions do not cause the dropdown to silently move the group last.
     */
    public function transform(mixed $order): ?FieldGroup
    {
        if (!$order) {
            return null;
        }

        return $this->fieldGroupRepository->findNextByOrder((int) $order);
    }

    /**
     * Selected "appear before" group -> its order (the target slot). Null (no
     * selection) means place last; FieldGroupModel appends in that case.
     */
    public function reverseTransform(mixed $group): ?int
    {
        return $group instanceof FieldGroup ? $group->getOrder() : null;
    }
}
