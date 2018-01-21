<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Transport;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\Swiftmailer\Transport\ElasticemailTransport;
use Mautic\LeadBundle\Entity\DoNotContact;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class ElasticemailTransportTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $transportCallback;
    private $logger;

    public function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator->method('trans')
            ->willReturnCallback(
                function ($key) {
                    return $key;
                }
            );

        $this->transportCallback = $this->getMockBuilder(TransportCallback::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = new Logger('test');
    }

    public function testUnsubscribedIsProcessed()
    {
        $status = 'AbuseReport';
        $this->transportCallback->expects($this->never())
            ->method('addFailureByHashId');

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'test@test.com',
                $status,
                DoNotContact::UNSUBSCRIBED
            );

        $transport = new ElasticemailTransport($this->translator, $this->logger, $this->transportCallback);

        $transport->processCallbackRequest($this->getRequestWithPayload($status));
    }

    public function testAbuseReportIsProcessed()
    {
        $status = 'Unsubscribed';
        $this->transportCallback->expects($this->never())
            ->method('addFailureByHashId');

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'test@test.com',
                $status,
                DoNotContact::UNSUBSCRIBED
            );

        $transport = new ElasticemailTransport($this->translator, $this->logger, $this->transportCallback);

        $transport->processCallbackRequest($this->getRequestWithPayload($status));
    }

    public function testSpamReportIsProcessed()
    {
        $status = 'Something';
        $this->transportCallback->expects($this->never())
            ->method('addFailureByHashId');

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'test@test.com',
                $status,
                DoNotContact::UNSUBSCRIBED
            );

        $transport = new ElasticemailTransport($this->translator, $this->logger, $this->transportCallback);

        $transport->processCallbackRequest($this->getRequestWithPayload($status, 'Spam'));
    }

    public function testBounceReportIsProcessed()
    {
        $bounceCategories = ['NotDelivered', 'NoMailbox', 'AccountProblem', 'DNSProblem', 'Unknown'];

        $this->transportCallback->expects($this->never())
            ->method('addFailureByHashId');

        $this->transportCallback->expects($this->exactly(5))
            ->method('addFailureByAddress')
            ->withConsecutive(
                ['test@test.com', 'NotDelivered', DoNotContact::BOUNCED],
                ['test@test.com', 'NoMailbox', DoNotContact::BOUNCED],
                ['test@test.com', 'AccountProblem', DoNotContact::BOUNCED],
                ['test@test.com', 'DNSProblem', DoNotContact::BOUNCED],
                ['test@test.com', 'Unknown', DoNotContact::BOUNCED]
            );

        $transport = new ElasticemailTransport($this->translator, $this->logger, $this->transportCallback);

        foreach ($bounceCategories as $cat) {
            $transport->processCallbackRequest($this->getRequestWithPayload('Bounce', $cat));
        }
    }

    public function testErrorReportIsProcessed()
    {
        $status = 'Error';
        $this->transportCallback->expects($this->never())
            ->method('addFailureByHashId');

        $this->transportCallback->expects($this->once())
            ->method('addFailureByAddress')
            ->with(
                'test@test.com',
                'mautic.email.complaint.reason.unknown',
                DoNotContact::BOUNCED
            );

        $transport = new ElasticemailTransport($this->translator, $this->logger, $this->transportCallback);

        $transport->processCallbackRequest($this->getRequestWithPayload($status));
    }

    /**
     * @param        $status
     * @param string $category
     *
     * @return Request
     */
    private function getRequestWithPayload($status, $category = 'Ignore')
    {
        $query   = [
            'status'      => $status,
            'category'    => $category,
            'account'     => 'account@test.com',
            'transaction' => '486de632-e3b1-40fd-ba29-807b8b13aa22',
            'to'          => 'test@test.com',
            'date'        => '12/22/2017 9:03:39 PM',
            'channel'     => 'testchannel',
            'subject'     => 'test',
        ];

        return new Request($query);
    }
}
