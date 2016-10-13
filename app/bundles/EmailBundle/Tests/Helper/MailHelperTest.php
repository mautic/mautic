<?php
/**
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException;
use Mautic\EmailBundle\Tests\Helper\Transport\BatchTransport;

class MailHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Mautic\EmailBundle\Swiftmailer\Exception\BatchQueueMaxException
     */
    public function testQueueModeThrowsExceptionWhenBatchLimitHit()
    {
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['mailer_return_path', false, null],
                        ['mailer_spool_type', false, 'memory'],
                    ]
                )
            );

        $swiftMailer = new \Swift_Mailer(new BatchTransport());

        $mailer = new \Mautic\EmailBundle\Helper\MailHelper($mockFactory, $swiftMailer, ['nobody@nowhere.com' => 'No Body']);

        // Enable queue mode
        $mailer->enableQueue();
        $mailer->addTo('somebody@somewhere.com');
        $mailer->addTo('somebodyelse@somewhere.com');
    }

    public function testQueueModeDisabledDoesNotThrowsExceptionWhenBatchLimitHit()
    {
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFactory->method('getParameter')
            ->will(
                $this->returnValueMap(
                    [
                        ['mailer_return_path', false, null],
                        ['mailer_spool_type', false, 'memory'],
                    ]
                )
            );

        $swiftMailer = new \Swift_Mailer(new BatchTransport());

        $mailer = new \Mautic\EmailBundle\Helper\MailHelper($mockFactory, $swiftMailer, ['nobody@nowhere.com' => 'No Body']);

        // Enable queue mode
        try {
            $mailer->addTo('somebody@somewhere.com');
            $mailer->addTo('somebodyelse@somewhere.com');
        } catch (BatchQueueMaxException $exception) {
            $this->fail('BatchQueueMaxException thrown');
        }

        // Otherwise success
        $this->assertTrue(true);
    }
}
