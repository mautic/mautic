<?php

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

    public function __construct(array $parameters)
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

    public function setAbTestResults(array $abTestResults)
    {
        $this->abTestResults = $abTestResults;
    }
}
