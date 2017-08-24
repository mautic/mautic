<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Model;

use JMS\Serializer\Serializer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Model\WebhookModel;

class WebhookModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEventsOrderbyDirWhenSetInWebhook()
    {
        $webhook    = (new Webhook())->setEventsOrderbyDir('DESC');
        $model      = $this->initModel('ASC');
        $orderbyDir = $model->getEventsOrderbyDir($webhook);

        $this->assertEquals('DESC', $orderbyDir);
    }

    public function testGetEventsOrderbyDirWhenNotSetInWebhook()
    {
        $model      = $this->initModel('DESC');
        $orderbyDir = $model->getEventsOrderbyDir();

        $this->assertEquals('DESC', $orderbyDir);
    }

    public function testGetEventsOrderbyDirWhenWebhookNotProvided()
    {
        $model      = $this->initModel('DESC');
        $orderbyDir = $model->getEventsOrderbyDir();

        $this->assertEquals('DESC', $orderbyDir);
    }

    protected function initModel($config = 'someValue')
    {
        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parametersHelper->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue($config));

        $serializer = $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notificationModel = $this->getMockBuilder(NotificationModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new WebhookModel($parametersHelper, $serializer, $notificationModel);
    }
}
