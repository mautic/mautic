<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\Middleware\ConfigAwareTrait;

class ParamsLoaderHelper
{
    use ConfigAwareTrait;

    private $parameters = [];

    /**
     * Get parameters for static method.
     *
     * @return array
     */
    public function getParameters()
    {
        if (empty($this->parameters)) {
            $this->parameters = $this->getConfig();
        }

        return $this->parameters;
    }
}
