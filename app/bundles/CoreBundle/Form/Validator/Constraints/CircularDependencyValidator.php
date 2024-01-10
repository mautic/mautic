<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Mautic\CoreBundle\Helper\Tree\IntNode;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Services\SegmentDependencyTreeFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    public function __construct(private SegmentDependencyTreeFactory $segmentDependencyTreeFactory)
    {
        $this->segmentDependencyTreeFactory = $segmentDependencyTreeFactory;
    }

    /**
     * @param LeadList|mixed $segment
     */
    public function validate($segment, Constraint $constraint)
    {
        if (!$constraint instanceof CircularDependency) {
            throw new UnexpectedTypeException($constraint, CircularDependency::class);
        }

        if (!$segment instanceof LeadList) {
            return;
        }

        $parentNode     = $this->segmentDependencyTreeFactory->buildTree($segment);
        $directChildren = $parentNode->getChildrenArray();

        $segmentIdsToCheck = array_map(fn ($child) => $child->getValue(), $directChildren);

        foreach ($directChildren as $directChildNode) {
            /** @var \RecursiveIteratorIterator<IntNode> $iterator */
            $iterator = new \RecursiveIteratorIterator($directChildNode, \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($iterator as $childNode) {
                if (in_array($childNode->getValue(), $segmentIdsToCheck)) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('filters')
                        ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                        ->setParameter('%segments%', "{$segment->getName()} > {$this->getSegmentCiclePath($childNode)}")
                        ->addViolation();

                    return;
                }

                if ($segment->getId() && $childNode->getValue() === $segment->getId()) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('filters')
                        ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                        ->setParameter('%segments%', "{$this->getSegmentCiclePath($childNode)} > {$segment->getName()}")
                        ->addViolation();

                    return;
                }
            }
        }
    }

    private function getSegmentCiclePath(IntNode $node): string
    {
        $path = [];

        while ($node->getParent()) {
            $path[] = $node->getParam('name');
            $node   = $node->getParent();
        }

        return implode(' > ', $path);
    }
}