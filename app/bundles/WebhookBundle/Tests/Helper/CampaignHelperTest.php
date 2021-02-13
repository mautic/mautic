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
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignHelperTest extends \PHPUnit\Framework\TestCase
{
    private $contact;
    private $connector;
    private $companyModel;
    private $companyRepository;

    /**
     * @var ArrayCollection
     */
    private $ipCollection;

    /**
     * @var CampaignHelper
     */
    private $campaignHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact           = $this->createMock(Lead::class);
        $this->connector         = $this->createMock(Http::class);
        $this->companyModel      = $this->createMock(CompanyModel::class);
        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $this->ipCollection      = new ArrayCollection();
        $this->companyRepository = $this->getMockBuilder(CompanyRepository::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCompaniesByLeadId'])
        ->getMock();

        $this->companyRepository->method('getCompaniesByLeadId')
        ->willReturn([new Company()]);

        $this->companyModel->method('getRepository')
        ->willReturn($this->companyRepository);

        $this->campaignHelper = new CampaignHelper($this->connector, $this->companyModel, $this->dispatcher);

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
            ->with('https://mautic.org', ['test'  => 'tee', 'email' => 'john@doe.email', 'IP' => '127.0.0.1,127.0.0.2'], ['test' => 'tee', 'company' => 'Mautic'], 10)
            ->willReturn((object) ['code' => 200]);

        $this->campaignHelper->fireWebhook($config, $this->contact);
    }

    public function testFireWebhookWithPostJson()
    {
        $config      = $this->provideSampleConfig('post');
        $expectedUrl = 'https://mautic.org?test=tee&email=john%40doe.email&IP=127.0.0.1%2C127.0.0.2';

        $config      = $this->provideSampleConfig('post', 'application/json');
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

    private function provideSampleConfig($method = 'get', $type = 'application/x-www-form-urlencoded')
    {
        $sample = [
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
        if ('application/json' == $type) {
            array_push($sample['headers']['list'],
            [
                'label' => 'content-type',
                'value' => 'application/json',
            ]);
        }

        return $sample;
    }
}
