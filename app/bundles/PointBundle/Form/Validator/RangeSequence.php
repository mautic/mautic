<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PointBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RangeSequence extends Constraint
{
    public $message = 'The range sequence is out of order.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}