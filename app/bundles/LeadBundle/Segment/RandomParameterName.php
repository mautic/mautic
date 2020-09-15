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
     * Generate a unique parameter name from int to base32 conversion.
     * This eliminates chance for parameter name collision.
     *
     * @see https://blog.jgrossi.com/2013/generating-ids-like-youtube-or-bit-ly-using-php/
     */
    public function generateRandomParameterName(): string
    {
        $base  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        ++$this->lastUsedParameterId;
        $value = (string) $this->lastUsedParameterId;

        $limit  = strlen($value);
        $result = strpos($base, $value[0]);

        for ($i = 1; $i < $limit; ++$i) {
            $result = 32 * $result + strpos($base, $value[$i]);
        }

        return 'par'.$result;
    }
}
