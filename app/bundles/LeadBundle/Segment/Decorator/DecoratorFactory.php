<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

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
     *
     * @param ContactSegmentFilterDictionary $contactSegmentFilterDictionary
     * @param BaseDecorator                  $baseDecorator
     * @param CustomMappedDecorator          $customMappedDecorator
     * @param DateOptionFactory              $dateOptionFactory
     * @param CompanyDecorator               $companyDecorator
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
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
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

        if (empty($this->contactSegmentFilterDictionary[$originalField])) {
            if ($contactSegmentFilterCrate->isCompanyType()) {
                return $this->companyDecorator;
            }

            return $this->baseDecorator;
        }

        return $this->customMappedDecorator;
    }
}
