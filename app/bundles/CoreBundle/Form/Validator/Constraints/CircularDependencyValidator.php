<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    public function __construct(
        private ListModel $model,
        private RequestStack $requestStack
    ) {
    }

    /**
     * @param array $filters
     */
    public function validate($filters, Constraint $constraint): void
    {
        $dependentSegmentIds = $this->flatten(array_map(fn ($id) => $this->reduceToSegmentIds($this->model->getEntity($id)->getFilters()), $this->reduceToSegmentIds($filters)));

        try {
            $segmentId = $this->getSegmentIdFromRequest();
            if (in_array($segmentId, $dependentSegmentIds)) {
                $this->context->addViolation($constraint->message);
            }
        } catch (\UnexpectedValueException) {
            // Segment ID is not in the request. May be new segment.
        }
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function getSegmentIdFromRequest(): int
    {
        $request     = $this->requestStack->getCurrentRequest();
        $routeParams = $request->get('_route_params');

        if (empty($routeParams['objectId'])) {
            throw new \UnexpectedValueException('Segment ID is missing in the request');
        }

        return (int) $routeParams['objectId'];
    }

    private function reduceToSegmentIds(array $filters): array
    {
        $segmentFilters = array_filter($filters, fn (array $filter): bool => 'leadlist' === $filter['type']
            && in_array($filter['operator'], [OperatorOptions::IN, OperatorOptions::NOT_IN]));

        $segentIdsInFilter = array_map(function (array $filter) {
            $bcValue = $filter['filter'] ?? [];

            return $filter['properties']['filter'] ?? $bcValue;
        }, $segmentFilters);

        return $this->flatten($segentIdsInFilter);
    }

    private function flatten(array $array): array
    {
        return array_unique(array_reduce($array, 'array_merge', []));
    }
}
