<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace Mautic\SmsBundle\Tests\Sms;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\SmsBundle\Api\TwilioApi;
use Mautic\SmsBundle\Sms\TransportChain;

class TransportChainTest extends AbstractMauticTestCase
{
    /**
     * @var TransportChain
     */
    private $transportChain;

    private $twilioTransport;

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param array  $parameters array of parameters to pass into method
     *
     * @throws \ReflectionException
     *
     * @return mixed method return
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function setUp()
    {
        parent::setUp();

        $this->transportChain = new TransportChain(
            'mautic.test.twilio.mock', $this->container->get('mautic.helper.integration'), $this->container->get('logger')
        );

        $this->twilioTransport = $this->getMockBuilder(TwilioApi::class)
                                      ->disableOriginalConstructor()->getMock();

        $this->twilioTransport
            ->method('sendSMS')
            ->will($this->returnValue('lol'));
    }

    public function testAddTransport()
    {
        $count = count($this->transportChain->getTransports());

        $this->transportChain->addTransport('mautic.transport.test', $this->container->get('mautic.sms.transport.twilio'), 'mautic.transport.test', 'Twilio');

        $this->assertCount($count + 1, $this->transportChain->getTransports());
    }

    public function testSendSms()
    {
        $this->transportChain->addTransport('mautic.test.twilio.mock', $this->twilioTransport, 'mautic.test.twilio.mock', 'Twilio');

        $this->assertEquals($this->transportChain->sendSms('+123456789', 'Yeah'), 'lol');
    }
}
