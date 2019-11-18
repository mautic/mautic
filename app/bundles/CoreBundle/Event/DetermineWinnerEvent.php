<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DetermineWinnerEvent extends Event
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $abTestResults;

    /**
     * @param array $parameters
     */
    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getAbTestResults()
    {
        return $this->abTestResults;
    }

    /**
     * @param array $abTestResults
     */
    public function setAbTestResults($abTestResults)
    {
        $this->abTestResults = $abTestResults;
    }
}
