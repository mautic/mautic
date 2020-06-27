<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Joomla\Http\Http;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\WebhookBundle\Helper\CampaignHelper;

class CampaignHelperTest extends \PHPUnit\Framework\TestCase
{
    private $contact;
    private $connector;

    /**
     * @var ArrayCollection
     */
    private $ipCollection;

    /**
     * @var CampaignHelper
     */
    private $campaignHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->contact        = $this->createMock(Lead::class);
        $this->connector      = $this->createMock(Http::class);
        $this->ipCollection   = new ArrayCollection();
        $this->campaignHelper = new CampaignHelper($this->connector);

        $this->ipCollection->add((new IpAddress())->setIpAddress('127.0.0.1'));
        $this->ipCollection->add((new IpAddress())->setIpAddress('127.0.0.2'));

        $this->contact->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(['email' => 'john@doe.email', 'company' => 'Mautic']);

        $this->contact->expects($this->once())
            ->method('getIpAddresses')
            ->willReturn($this->ipCollection);
    }

    public function testFireWebhookWithGet()
    {
        $expectedUrl = 'https://mautic.org?test=tee&email=john%40doe.email&IP=127.0.0.1%2C127.0.0.2';

        $this->connector->expects($this->once())
            ->method('get')
            ->with($expectedUrl, ['test' => 'tee', 'company' => 'Mautic'], 10)
            ->willReturn((object) ['code' => 200]);

        $this->campaignHelper->fireWebhook($this->provideSampleConfig(), $this->contact);
    }

    public function testFireWebhookWithPost()
    {
        $config      = $this->provideSampleConfig('post');
        $expectedUrl = 'https://mautic.org?test=tee&email=john%40doe.email&IP=127.0.0.1%2C127.0.0.2';

        $this->connector->expects($this->once())
            ->method('post')
            ->with('https://mautic.org', json_encode(['test'  => 'tee', 'email' => 'john@doe.email', 'IP' => '127.0.0.1,127.0.0.2']), ['test' => 'tee', 'company' => 'Mautic', 'content-type' => 'application/json'], 10)
            ->willReturn((object) ['code' => 200]);

        $this->campaignHelper->fireWebhook($config, $this->contact);
    }

    public function testFireWebhookWhenReturningNotFound()
    {
        $this->connector->expects($this->once())
            ->method('get')
            ->willReturn((object) ['code' => 404]);

        $this->expectException(\OutOfRangeException::class);

        $this->campaignHelper->fireWebhook($this->provideSampleConfig(), $this->contact);
    }

    private function provideSampleConfig($method = 'get')
    {
        return [
            'url'             => 'https://mautic.org',
            'method'          => $method,
            'timeout'         => 10,
            'additional_data' => [
                'list' => [
                    [
                        'label' => 'test',
                        'value' => 'tee',
                    ],
                    [
                        'label' => 'email',
                        'value' => '{contactfield=email}',
                    ],
                    [
                        'label' => 'IP',
                        'value' => '{contactfield=ipAddress}',
                    ],
                ],
            ],
            'headers' => [
                'list' => [
                    [
                        'label' => 'test',
                        'value' => 'tee',
                    ],
                    [
                        'label' => 'company',
                        'value' => '{contactfield=company}',
                    ],
                ],
            ],
        ];
    }
}
