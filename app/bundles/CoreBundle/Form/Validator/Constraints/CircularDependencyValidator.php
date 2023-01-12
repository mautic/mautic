<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UnexpectedValueException;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class CircularDependencyValidator extends ConstraintValidator
{
    /**
     * @var ListModel
     */
    private $model;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(ListModel $model, RequestStack $requestStack)
    {
        $this->model        = $model;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array $filters
     */
    public function validate($filters, Constraint $constraint)
    {
        $dependentSegmentIds = $this->flatten(array_map(function ($id) {
            return $this->reduceToSegmentIds($this->model->getEntity($id)->getFilters());
        }, $this->reduceToSegmentIds($filters)));

        try {
            $segmentId = $this->getSegmentIdFromRequest();
            if (in_array($segmentId, $dependentSegmentIds)) {
                $this->context->addViolation($constraint->message);
            }
        } catch (UnexpectedValueException $e) {
            // Segment ID is not in the request. May be new segment.
        }
    }

    /**
     * @return int
     *
     * @throws UnexpectedValueException
     */
    private function getSegmentIdFromRequest()
    {
        $request     = $this->requestStack->getCurrentRequest();
        $routeParams = $request->get('_route_params');

        if (empty($routeParams['objectId'])) {
            throw new UnexpectedValueException('Segment ID is missing in the request');
        }

        return (int) $routeParams['objectId'];
    }

    /**
     * @return array
     */
    private function reduceToSegmentIds(array $filters)
    {
        $segmentFilters = array_filter($filters, function (array $filter) {
            return 'leadlist' === $filter['type']
                && in_array($filter['operator'], [OperatorOptions::IN, OperatorOptions::NOT_IN]);
        });

        $segentIdsInFilter = array_map(function (array $filter) {
            $bcValue = $filter['filter'] ?? [];

            return $filter['properties']['filter'] ?? $bcValue;
        }, $segmentFilters);

        return $this->flatten($segentIdsInFilter);
    }

    /**
     * @return array
     */
    private function flatten(array $array)
    {
        return array_unique(array_reduce($array, 'array_merge', []));
    }
}
