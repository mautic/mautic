<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\PointEventHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class PointEventHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testSendEmail()
    {
        $helper = new PointEventHelper();
        $lead   = new Lead();
        $lead->setFields([
            'core' => [
                'email' => [
                    'value' => 'test@test.com',
                ],
            ],
        ]);
        $event = [
            'id'         => 1,
            'properties' => [
                'email' => 1,
            ],
        ];

        $result = $helper->sendEmail($event, $lead, $this->getMockEmail(), $this->getMockLead());
        $this->assertEquals(true, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockEmail(false), $this->getMockLead());
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockEmail(true, false), $this->getMockLead());
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, new Lead(), $this->getMockEmail(true, false), $this->getMockLead());
        $this->assertEquals(false, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockLead()
    {
        $mock = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $mock;
    }

    /**
     * @param bool $published
     * @param bool $success
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
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
