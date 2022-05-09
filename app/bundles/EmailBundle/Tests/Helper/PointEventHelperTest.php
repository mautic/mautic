<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Factory\MauticFactory;
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

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory());
        $this->assertEquals(true, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory(false));
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, $lead, $this->getMockMauticFactory(true, false));
        $this->assertEquals(false, $result);

        $result = $helper->sendEmail($event, new Lead(), $this->getMockMauticFactory(true, false));
        $this->assertEquals(false, $result);
    }

    public function testValidateEmail(): void
    {
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $categoryMock->expects($this->any())
            ->method('getId')->willReturn(1);

        $emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getCategory'])
            ->getMock();
        $emailMock->expects($this->any())
            ->method('getId')->willReturn(4);
        $emailMock->expects($this->any())
            ->method('getCategory')->willReturn($categoryMock);

        // validate email id in point action
        $this->assertEquals(true, PointEventHelper::validateEmail($emailMock, [
            'properties' => [
                'emails' => [
                    0 => '4',
                ],
                'categories'  => [],
                'triggerMode' => 'internalId',
            ],
        ]));

        // validate email id not in point action
        $this->assertEquals(false, PointEventHelper::validateEmail($emailMock, [
            'properties' => [
                'emails' => [
                    0 => '21',
                ],
                'categories'  => [],
                'triggerMode' => 'internalId',
            ],
        ]));

        // validate category id in point action
        $this->assertEquals(true, PointEventHelper::validateEmail($emailMock, [
            'properties' => [
                'emails'     => [],
                'categories' => [
                    0 => 1,
                ],
                'triggerMode' => 'internalId',
            ],
        ]));

        // validate category id not in point action
        $this->assertEquals(false, PointEventHelper::validateEmail($emailMock, [
            'properties' => [
                'emails'     => [],
                'categories' => [
                    0 => 5,
                ],
                'triggerMode' => 'internalId',
            ],
        ]));
    }

    /**
     * @param bool $published
     * @param bool $success
     *
     * @return \PHPUnit\Framework\MockObject\MockObject&MauticFactory
     */
    private function getMockMauticFactory($published = true, $success = true)
    {
        $mock = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getModel'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getModel')
            ->willReturnCallback(function ($model) use ($published, $success) {
                switch ($model) {
                    case 'email':
                        return $this->getMockEmailModel($published, $success);
                    case 'lead':
                        return $this->createMock(LeadModel::class);
                }
            });

        return $mock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&EmailModel
     */
    private function getMockEmailModel(bool $published = true, bool $success = true)
    {
        $sendEmail = $success ? true : ['error' => 1];

        $mock = $this->getMockBuilder(EmailModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntity', 'sendEmail'])
            ->getMock();

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
