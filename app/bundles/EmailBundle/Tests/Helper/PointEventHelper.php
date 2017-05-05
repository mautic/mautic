<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\PointEventHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Model\LeadModel;

class PointEventHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testSendEmail()
    {
        $helper = new PointEventHelper();
        $lead = new Lead();
        $lead->setFields([
            'core' => [
                'email' => [
                    'value' => 'test@test.com'
                ]
            ]
        ]);
        $event = [
            'id' => 1,
            'properties' => [
                'email' => 1
            ]
        ];

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory());
        $this->assertEquals(true, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory(false));
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory(true, false));
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, new Lead(), $this->getMockMauticFactory(true, false));
        $this->assertEquals(false, $result);
    }

    /**
     * @param bool $published
     * @param bool $success
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockMauticFactory($published = true, $success = true)
    {
        $mock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getModel'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getModel')
            ->willReturnCallback(function ($model) use ($published, $success){
                switch ($model) {
                    case 'email':
                        return $this->getMockEmail($published, $success);
                    case 'lead':
                        return $this->getMockLead();
                }
            });

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockLead()
    {
        $mock = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['flattenFields'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('flattenFields')
            ->willReturn([]);

        return $mock;
    }

    /**
     * @param bool $published
     * @param bool $success
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockEmail($published = true, $success = true)
    {
        $sendEmail = $success ? true : ['error' => 1];

        $mock = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntity', 'sendEmail'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getEntity')
            ->willReturnCallback(function ($id) use ($published) {
                $email = new Email();
                $email->setIsPublished($published);

                return $email;
            });

        $mock->expects($this->any())
            ->method('sendEmail')
            ->willReturn($sendEmail);

        return $mock;
    }

}
