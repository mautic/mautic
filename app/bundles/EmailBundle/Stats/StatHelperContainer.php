<?php

namespace Mautic\EmailBundle\Stats;

use Mautic\EmailBundle\Stats\Exception\InvalidStatHelperException;
use Mautic\EmailBundle\Stats\Helper\StatHelperInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class StatHelperContainer
{
    /**
     * @var array<string, StatHelperInterface>
     */
    private array $helpers = [];

<<<<<<< HEAD
    public function __construct(
        #[AutowireIterator(tag: 'mautic.email_stat_helper')]
        iterable $helpersIterator,
    ) {
        foreach ($helpersIterator as $helper) {
            $this->helpers[$helper->getName()] = $helper;
        }
=======
    /**
     * @param iterable<StatHelperInterface> $statHelpers
     */
    public function __construct(
        #[AutowireIterator('mautic.email_stat_helper')]
        iterable $statHelpers = [],
    ) {
        foreach ($statHelpers as $statHelper) {
            $this->addHelper($statHelper);
        }
    }

    private function addHelper(StatHelperInterface $helper): void
    {
        $this->helpers[$helper->getName()] = $helper;
>>>>>>> bcbf4a0307 ([di] flip compiler pass to #[AutowireIterator])
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
