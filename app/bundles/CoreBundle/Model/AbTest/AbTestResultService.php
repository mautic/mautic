<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Factory\MauticFactory;

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
     * AbTestResultService constructor.
     *
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param VariantEntityInterface $parentVariant
     * @param $criteria
     *
     * @return array|mixed
     *
     * @throws \ReflectionException
     */
    public function getAbTestResult(VariantEntityInterface $parentVariant, $criteria)
    {
        //get A/B test information
        list($parent, $children) = $parentVariant->getVariants();

        $abTestResults = [];
        if (isset($criteria)) {
            $testSettings = $criteria;

            $args = [
                'factory'    => $this->factory,
                'email'      => $parentVariant,
                'parent'     => $parent,
                'children'   => $children,
            ];

            //execute the callback
            if (is_callable($testSettings['callback'])) {
                if (is_array($testSettings['callback'])) {
                    $reflection = new \ReflectionMethod($testSettings['callback'][0], $testSettings['callback'][1]);
                } elseif (strpos($testSettings['callback'], '::') !== false) {
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
        }

        return $abTestResults;
    }
}
