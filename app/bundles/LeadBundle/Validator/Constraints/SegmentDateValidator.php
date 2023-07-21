<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SegmentDateValidator extends ConstraintValidator
{
    public function __construct(private RequestStack $requestStack, private ContactSegmentFilterFactory $contactSegmentFilterFactory, private TranslatorInterface $translator)
    {
    }

    /**
     * @param array $filters
     */
    public function validate($filters, Constraint $constraint)
    {
        $filters = $this->getFiltersFromRequest();
        foreach ($filters as $filter) {
            if (isset($filter['type']) && in_array($filter['type'], ['date', 'datetime'])) {
                $segmentFilter  = $this->contactSegmentFilterFactory->factorSegmentFilter($filter);
                $parameterValue = $segmentFilter->getParameterValue();
                if (is_array($parameterValue)) {
                    continue;
                }

                $dateString = str_replace('%', '', $parameterValue);

                $format = 'Y-m-d'; // Set the format you want.

                $dateTime = \DateTime::createFromFormat($format, $dateString);
                if (false === $dateTime) {
                    $this->context->addViolation($this->translator->trans($constraint->message, ['%value%' => $parameterValue], 'validators'));

                    return;
                }
            }
        }
    }

    /**
     * @return array|mixed
     */
    protected function getFiltersFromRequest(): mixed
    {
        $request = $this->requestStack->getCurrentRequest();
        $params  = $request->request->get('leadlist') ?? [];
        $filters = $params['filters'] ?? [];

        return $filters;
    }
}
