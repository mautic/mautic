<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a company segment does not reference itself through other segments.
 * Prevents circular dependencies like: Segment A → Segment B → Segment A.
 */
class CompanySegmentCircularReferenceValidator extends ConstraintValidator
{
    public function __construct(
        private CompanySegmentModel $model,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @phpstan-param array<mixed>|mixed $filters
     */
    public function validate($filters, Constraint $constraint): void
    {
        if (!$constraint instanceof CompanySegmentCircularReference) {
            throw new UnexpectedTypeException($constraint, CompanySegmentCircularReference::class);
        }

        if (!is_array($filters)) {
            throw new UnexpectedTypeException($filters, 'array');
        }

        /** @var array<array<mixed>> $filters */
        $dependentSegmentIds = $this->flatten(array_map(function ($id): array {
            if (!is_int($id)) {
                $id = null;
            }
            $entity = $this->model->getEntity($id);
            assert($entity instanceof CompanySegment);

            return $this->reduceToSegmentIds($entity->getFilters());
        }, $this->reduceToSegmentIds($filters)));

        try {
            $segmentId = $this->getSegmentIdFromRequest();
            if (in_array($segmentId, $dependentSegmentIds, true)) {
                $this->context->addViolation($constraint->message);
            }
        } catch (\UnexpectedValueException) {
            // Segment ID is not in the request. May be new segment.
        }
    }

    private function getSegmentIdFromRequest(): int
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \UnexpectedValueException('Request is null.');
        }

        $routeParams = $request->get('_route_params');

        if (!is_array($routeParams) || !isset($routeParams['objectId']) || !is_numeric($routeParams['objectId'])) {
            throw new \UnexpectedValueException('Segment ID is missing in the request');
        }

        return (int) $routeParams['objectId'];
    }

    /**
     * Extract segment IDs from filters array.
     *
     * @param array<array<mixed>> $filters
     *
     * @return array<mixed>
     */
    private function reduceToSegmentIds(array $filters): array
    {
        $segmentFilters = array_filter($filters, static fn (array $filter): bool => CompanySegmentModel::PROPERTIES_FIELD === $filter['type']
            && in_array($filter['operator'], [OperatorOptions::IN, OperatorOptions::NOT_IN], true));

        /** @var array<array<mixed>> $segmentIdsInFilter */
        $segmentIdsInFilter = array_map(static function (array $filter) {
            $bcValue = $filter['filter'] ?? [];
            if (is_array($filter['properties']) && !array_key_exists('filter', $filter['properties'])) {
                return $bcValue;
            }

            /** @phpstan-ignore-next-line */
            return $filter['properties']['filter'] ?? $bcValue;
        }, $segmentFilters);

        return $this->flatten($segmentIdsInFilter);
    }

    /**
     * Flatten and deduplicate array.
     *
     * @param array<array<mixed>> $array
     *
     * @return array<mixed>
     */
    private function flatten(array $array): array
    {
        return array_unique(array_reduce($array, 'array_merge', []));
    }
}
