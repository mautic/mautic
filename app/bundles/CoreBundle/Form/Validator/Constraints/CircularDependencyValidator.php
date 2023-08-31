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
 * Throws an error if a circular dependency is detected in the segment tree.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    public function __construct(
        private SegmentDependencyTreeFactory $segmentDependencyTreeFactory,
    ) {
    }

    /**
     * @param LeadList|mixed $segment
     */
    public function validate($segment, Constraint $constraint): void
    {
        if (!$constraint instanceof CircularDependency) {
            throw new UnexpectedTypeException($constraint, CircularDependency::class);
        }

        if (!$segment instanceof LeadList) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator($this->segmentDependencyTreeFactory->buildTree($segment), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $leafNode) {
            if ($leafNode->getParam('circular')) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('filters')
                    ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setParameter('%segments%', "{$this->getSegmentCiclePath($leafNode)} > {$segment->getName()}")
                    ->addViolation();

                return;
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
