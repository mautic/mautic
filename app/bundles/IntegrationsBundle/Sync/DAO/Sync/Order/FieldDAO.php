<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class FieldDAO
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var NormalizedValueDAO
     */
    private $value;

    /**
     * @param string             $name
     * @param NormalizedValueDAO $value
     */
    public function __construct($name, NormalizedValueDAO $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return NormalizedValueDAO
     */
    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
    }
}
