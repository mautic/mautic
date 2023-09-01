<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Mautic\CoreBundle\Helper\Tree\IntNode;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Services\SegmentDependencyTreeFactory;
use RecursiveIteratorIterator;
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
    /**
     * @var SegmentDependencyTreeFactory
     */
    private $segmentDependencyTreeFactory;

    public function __construct(SegmentDependencyTreeFactory $segmentDependencyTreeFactory)
    {
        $this->segmentDependencyTreeFactory = $segmentDependencyTreeFactory;
    }

    /**
     * @param LeadList $leadList
     */
    public function validate($segment, Constraint $constraint)
    {
        if (!$constraint instanceof CircularDependency) {
            throw new UnexpectedTypeException($constraint, CircularDependency::class);
        }

        if (!$segment instanceof LeadList || !$segment->getId()) {
            return;
        }

        $parentNode = $this->segmentDependencyTreeFactory->buildTree($segment);

        /** @var RecursiveIteratorIterator<IntNode> $iterator */
        $iterator = new RecursiveIteratorIterator($parentNode, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $childNode) {
            if (((int) $segment->getId()) === $childNode->getValue()) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('filters')
                    ->setCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setParameter('%segments%', $childNode->getParent()->getValue())
                    ->addViolation();
            }
        }
    }
}
