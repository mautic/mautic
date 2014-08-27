<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Report
 *
 * Designed to provide forward compatibility with Symfony 2.6.
 * This class can be removed when Mautic requires Symfony 2.6 or later.
 */
class Report extends Constraint
{
    /**
     * Violation code marking an invalid form.
     */
    const ERR_INVALID = 1;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
