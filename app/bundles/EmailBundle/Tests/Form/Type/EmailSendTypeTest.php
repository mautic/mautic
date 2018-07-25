<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailSendTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testSendEmailFormBasedOnSpoolType()
    {
        $formBuilder             = $this->createMock(FormBuilderInterface::class);
        $routerMock              = $this->createMock(RouterInterface::class);
        $coreParameterHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParameterHelperMock->expects($this->any())->method('getParameter')->with('mailer_spool_type')->willReturn('file');

        $emailSendType = new EmailSendType($routerMock, $coreParameterHelperMock);
        $options       = ['with_immediately'=>true];
        $emailSendType->buildForm($formBuilder, $options);
    }
}
