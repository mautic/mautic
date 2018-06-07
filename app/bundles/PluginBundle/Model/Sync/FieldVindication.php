<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Model\Sync;

/**
 * Class FieldVindication.
 */
class FieldVindication
{
    /**
     * @var int|null
     */
    private $possibleChangeTimestamp = null;

    /**
     * @var int|null
     */
    private $certainChangeTimestamp = null;

    /**
     * @var mixed
     */
    private $value;

    /**
     * FieldVindication constructor.
     *
     * @param mixed    $value
     * @param int|null $certainChangeTimestamp
     * @param int|null $possibleChangeTimestamp
     */
    public function __construct($value, $certainChangeTimestamp = null, $possibleChangeTimestamp = null)
    {
        $this->value                   = $value;
        $this->certainChangeTimestamp  = $certainChangeTimestamp;
        $this->possibleChangeTimestamp = $possibleChangeTimestamp;
    }

    /**
     * @return int|null
     */
    public function getPossibleChangeTimestamp()
    {
        return $this->possibleChangeTimestamp;
    }

    /**
     * @return int|null
     */
    public function getCertainChangeTimestamp()
    {
        return $this->certainChangeTimestamp;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
