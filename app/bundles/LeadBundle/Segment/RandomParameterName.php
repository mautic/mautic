<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

class RandomParameterName
{
    /**
     * @var int
     */
    protected $lastUsedParameterId = 0;

    /**
     * Generate a unique parameter name from int using base conversion.
     * This eliminates chance for parameter name collision and provides unique result for each number.
     *
     * @see https://stackoverflow.com/questions/307486/short-unique-id-in-php/1516430#1516430
     */
    public function generateRandomParameterName(): string
    {
        $value = base_convert($this->lastUsedParameterId, 10, 36);

        ++$this->lastUsedParameterId;

        return 'par'.$value;
    }
}
