<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Translation\Translator;

abstract class CommonMocks extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Translator
     */
    protected function getTranslatorMock()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('hasId')
            ->will($this->returnValue(false));

        return $translator;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManagerMock()
    {
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $entityManager;
    }

    /**
     * @return PathsHelper
     */
    protected function getPathsHelperMock()
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $pathsHelper;
    }

    /**
     * @return CoreParametersHelper
     */
    protected function getCoreParametersHelperMock()
    {
        $paramHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $paramHelper;
    }

    /**
     * @return BundleHelper
     */
    protected function getBundleHelperMock()
    {
        $bundleHelper = $this->getMockBuilder(BundleHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $bundleHelper;
    }
}
