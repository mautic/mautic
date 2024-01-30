<?php

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AbTestResultService.
 */
class AbTestResultService
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * AbTestResultService constructor.
     */
    public function __construct(MauticFactory $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory         = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return array|mixed
     *
     * @throws \ReflectionException
     */
    public function getAbTestResult(VariantEntityInterface $parentVariant, $criteria)
    {
        // get A/B test information
        [$parent, $children] = $parentVariant->getVariants();

        $abTestResults = [];
        if (isset($criteria)) {
            $testSettings = $criteria;

            $args = [
                'factory'    => $this->factory,
                'email'      => $parentVariant,
                'parent'     => $parent,
                'children'   => $children,
            ];

            // execute the callback
            if (isset($testSettings['callback']) && is_callable($testSettings['callback'])) {
                if (is_array($testSettings['callback'])) {
                    $reflection = new \ReflectionMethod($testSettings['callback'][0], $testSettings['callback'][1]);
                } elseif (false !== strpos($testSettings['callback'], '::')) {
                    $parts      = explode('::', $testSettings['callback']);
                    $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                } else {
                    $reflection = new \ReflectionMethod(null, $testSettings['callback']);
                }

                $pass = [];
                foreach ($reflection->getParameters() as $param) {
                    if (isset($args[$param->getName()])) {
                        $pass[] = $args[$param->getName()];
                    } else {
                        $pass[] = null;
                    }
                }
                $abTestResults = $reflection->invokeArgs($this, $pass);
            }

            if (isset($testSettings['event'])) {
                $this->eventDispatcher->dispatch(
                    $event = new DetermineWinnerEvent($args),
                    $testSettings['event']
                );
                $abTestResults = $event->getAbTestResults();
            }
        }

        return $abTestResults;
    }
}
