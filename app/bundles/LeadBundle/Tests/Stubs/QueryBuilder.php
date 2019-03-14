<?php

namespace Mautic\LeadBundle\Tests\Stubs;

class QueryBuilder
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $methodCalls = [];

    /**
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, array $arguments)
    {
        if (!isset($this->methodCalls[$name])) {
            $this->methodCalls[$name] = [];
        }

        $this->methodCalls[$name] = $arguments;

        return $this;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
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
    public function getMethodCalls()
    {
        return $this->methodCalls;
    }
}
