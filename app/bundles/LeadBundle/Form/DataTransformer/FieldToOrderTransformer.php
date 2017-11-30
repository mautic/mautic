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

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\Form\DataTransformerInterface;

class FieldToOrderTransformer implements DataTransformerInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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

        $field = $this->em
            ->getRepository('MauticLeadBundle:LeadField')
            ->findOneBy(['order' => $order]);

        return $field;
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
