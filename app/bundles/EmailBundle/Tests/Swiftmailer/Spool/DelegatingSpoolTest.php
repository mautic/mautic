<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\Spool;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Swiftmailer\Spool\DelegatingSpool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DelegatingSpoolTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var \Swift_Transport|MockObject
     */
    private $realTransport;

    /**
     * @var \Swift_Mime_SimpleMessage|MockObject
     */
    private $message;

    protected function setUp()
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->realTransport        = $this->createMock(\Swift_Transport::class);
        $this->message              = $this->createMock(\Swift_Mime_SimpleMessage::class);
    }

    public function testEmailIsQueuedIfSpoolingIsEnabled()
    {
        $spoolPath = __DIR__.'/tmp';

        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(['mailer_spool_type'], ['mailer_spool_path'])
            ->willReturnOnConsecutiveCalls('file', $spoolPath);

        $spool = new DelegatingSpool($this->coreParametersHelper, $this->realTransport);

        $failed = [];
        $spool->delegateMessage($this->message, $failed);

        $this->assertTrue($spool->wasMessageSpooled());

        $finder = (new Finder())
            ->files()
            ->in($spoolPath)
            ->name('*.message');

        $this->assertEquals(1, count($finder));

        // Cleanup test files
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $messageFile = $file->getPathname();
            unlink($messageFile);
        }

        rmdir($spoolPath);
    }

    public function testEmailIsSentImmediatelyIfSpoolingIsDisabled()
    {
        $spoolPath = __DIR__.'/tmp';

        $this->coreParametersHelper->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(['mailer_spool_type'], ['mailer_spool_path'])
            ->willReturnOnConsecutiveCalls('memory', $spoolPath);

        $this->realTransport->expects($this->once())
            ->method('send')
            ->willReturn(1);

        $spool = new DelegatingSpool($this->coreParametersHelper, $this->realTransport);

        $failed = [];
        $sent   = $spool->delegateMessage($this->message, $failed);

        $this->assertFalse($spool->wasMessageSpooled());
        $this->assertEquals(1, $sent);

        rmdir($spoolPath);
    }
}
