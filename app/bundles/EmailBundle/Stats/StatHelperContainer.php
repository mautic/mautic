<?php

namespace Mautic\EmailBundle\Stats;

use Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException;
use Mautic\EmailBundle\Stats\Helper\StatHelperInterface;

class StatHelperContainer
{
    /**
     * @var array<string, StatHelperInterface>
     */
    private array $helpers = [];

    public function addHelper(StatHelperInterface $helper): void
    {
        $this->helpers[$helper->getName()] = $helper;
    }

    /**
     * @throws InvalidStatHelperException
     */
    public function getHelper($name): StatHelperInterface
    {
        if (!isset($this->helpers[$name])) {
            throw new InvalidStatHelperException($name.' has not been registered');
        }

        return $this->helpers[$name];
    }
}
