<?php

namespace Mautic\EmailBundle\Stats;

use Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException;
use Mautic\EmailBundle\Stats\Helper\StatHelperInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class StatHelperContainer
{
    /**
     * @var array<string, StatHelperInterface>
     */
    private array $helpers = [];

    public function __construct(
        #[AutowireIterator(tag: 'mautic.email_stat_helper')]
        iterable $helpersIterator,
    ) {
        foreach ($helpersIterator as $helper) {
            $this->helpers[$helper->getName()] = $helper;
        }
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
