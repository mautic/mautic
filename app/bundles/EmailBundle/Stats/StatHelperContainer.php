<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Stats;

use Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException;
use Mautic\EmailBundle\Stats\Helper\StatHelperInterface;

class StatHelperContainer
{
    private $helpers = [];

    public function addHelper(StatHelperInterface $helper)
    {
        $this->helpers[$helper->getName()] = $helper;
    }

    /**
     * @param $name
     *
     * @return StatHelperInterface
     *
     * @throws InvalidStatHelperException
     */
    public function getHelper($name)
    {
        if (!isset($this->helpers[$name])) {
            throw new InvalidStatHelperException($name.' has not been registered');
        }

        return $this->helpers[$name];
    }
}
