<?php


namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Exception\FilterNotFoundException;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;

/**
 * Class DecoratorFactory.
 */
class DecoratorFactory
{
    /**
     * @var ContactSegmentFilterDictionary
     */
    private $contactSegmentFilterDictionary;

    /**
     * @var BaseDecorator
     */
    private $baseDecorator;

    /**
     * @var CustomMappedDecorator
     */
    private $customMappedDecorator;

    /**
     * @var CompanyDecorator
     */
    private $companyDecorator;

    /**
     * @var DateOptionFactory
     */
    private $dateOptionFactory;

    /**
     * DecoratorFactory constructor.
     */
    public function __construct(
        ContactSegmentFilterDictionary $contactSegmentFilterDictionary,
        BaseDecorator $baseDecorator,
        CustomMappedDecorator $customMappedDecorator,
        DateOptionFactory $dateOptionFactory,
        CompanyDecorator $companyDecorator
    ) {
        $this->baseDecorator                  = $baseDecorator;
        $this->customMappedDecorator          = $customMappedDecorator;
        $this->dateOptionFactory              = $dateOptionFactory;
        $this->contactSegmentFilterDictionary = $contactSegmentFilterDictionary;
        $this->companyDecorator               = $companyDecorator;
    }

    /**
     * @return FilterDecoratorInterface
     */
    public function getDecoratorForFilter(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isDateType()) {
            $dateDecorator = $this->dateOptionFactory->getDateOption($contactSegmentFilterCrate);

            if ($contactSegmentFilterCrate->isCompanyType()) {
                return new DateCompanyDecorator($dateDecorator);
            }

            return $dateDecorator;
        }

        $originalField = $contactSegmentFilterCrate->getField();

        try {
            $this->contactSegmentFilterDictionary->getFilter($originalField);

            return $this->customMappedDecorator;
        } catch (FilterNotFoundException $e) {
            if ($contactSegmentFilterCrate->isCompanyType()) {
                return $this->companyDecorator;
            }

            return $this->baseDecorator;
        }
    }
}
