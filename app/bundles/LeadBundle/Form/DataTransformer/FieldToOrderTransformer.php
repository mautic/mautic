<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\DataTransformer;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Symfony\Component\Form\DataTransformerInterface;

class FieldToOrderTransformer implements DataTransformerInterface
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    public function __construct(LeadFieldRepository $leadFieldRepository)
    {
        $this->leadFieldRepository = $leadFieldRepository;
    }

    /**
     * Transforms an object to an integer (order).
     *
     * @param LeadField|null $order
     *
     * @return string
     */
    public function transform($order)
    {
        if (!$order) {
            return null;
        }

        return $this->leadFieldRepository->findOneBy(['order' => $order]);
    }

    /**
     * Transforms a integer to an object.
     *
     * @param int $field
     *
     * @return LeadField|null
     */
    public function reverseTransform($field)
    {
        if (null === $field) {
            return 0;
        }

        return $field->getOrder();
    }
}
