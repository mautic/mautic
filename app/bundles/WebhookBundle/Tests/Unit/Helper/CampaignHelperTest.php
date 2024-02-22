<?php

namespace Mautic\WebhookBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CampaignHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&Lead
     */
    private MockObject $contact;

    /**
     * @var MockObject|Client
     */
    private MockObject $client;

    /**
     * @var MockObject|CompanyModel
     */
    private MockObject $companyModel;

    /**
     * @var MockObject|CompanyRepository
     */
    private MockObject $companyRepository;

    /**
     * @var ArrayCollection<int,IpAddress>
     */
    private ArrayCollection $ipCollection;

    private CampaignHelper $campaignHelper;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact           = $this->createMock(Lead::class);
        $this->client            = $this->createMock(Client::class);
        $this->companyModel      = $this->createMock(CompanyModel::class);
        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $this->ipCollection      = new ArrayCollection();
        $this->companyRepository = $this->getMockBuilder(CompanyRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCompaniesByLeadId'])
            ->getMock();

        $this->companyRepository->method('getCompaniesByLeadId')->willReturn([new Company()]);

        $this->companyModel->method('getRepository')->willReturn($this->companyRepository);

        $this->campaignHelper = new CampaignHelper($this->client, $this->companyModel, $this->dispatcher);

        $this->ipCollection->add((new IpAddress())->setIpAddress('127.0.0.1'));
        $this->ipCollection->add((new IpAddress())->setIpAddress('127.0.0.2'));

        $this->contact->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(['email' => 'john@doe.email', 'company' => 'Mautic']);

        $this->contact->expects($this->once())
            ->method('getIpAddresses')
            ->willReturn($this->ipCollection);
    }

    public function testFireWebhookWithGet(): void
    {
        $expectedUrl = 'https://mautic.org?test=tee&email=john%40doe.email&IP=127.0.0.1%2C127.0.0.2';

        $this->client->expects($this->once())
            ->method('get')
            ->with($expectedUrl, [
                \GuzzleHttp\RequestOptions::HEADERS => ['test' => 'tee', 'company' => 'Mautic'],
                \GuzzleHttp\RequestOptions::TIMEOUT => 10,
            ])
            ->willReturn(new Response(200));

        $this->campaignHelper->fireWebhook($this->provideSampleConfig(), $this->contact);
    }

    public function testFireWebhookWithPost(): void
    {
        $config = $this->provideSampleConfig('post');

        $this->client->expects($this->once())
            ->method('request')
            ->with('post', 'https://mautic.org', [
                \GuzzleHttp\RequestOptions::FORM_PARAMS => ['test'  => 'tee', 'email' => 'john@doe.email', 'IP' => '127.0.0.1,127.0.0.2'],
                \GuzzleHttp\RequestOptions::HEADERS     => ['test' => 'tee', 'company' => 'Mautic'],
                \GuzzleHttp\RequestOptions::TIMEOUT     => 10,
            ])
            ->willReturn(new Response(200));

        $this->campaignHelper->fireWebhook($config, $this->contact);
    }

    public function testFireWebhookWithPostJson(): void
    {
        $config = $this->provideSampleConfig('post', 'application/json');
        $this->client->expects($this->once())
            ->method('request')
            ->with('post', 'https://mautic.org', [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'test'         => 'tee',
                    'company'      => 'Mautic',
                    'content-type' => 'application/json',
                ],
                \GuzzleHttp\RequestOptions::TIMEOUT => 10,
                \GuzzleHttp\RequestOptions::BODY    => json_encode(
                    ['test' => 'tee', 'email' => 'john@doe.email', 'IP' => '127.0.0.1,127.0.0.2']
                ),
            ])
            ->willReturn(new Response(200));

        $this->campaignHelper->fireWebhook($config, $this->contact);
    }

    public function testFireWebhookWhenReturningNotFound(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->willReturn(new Response(404));

        $this->expectException(\OutOfRangeException::class);

        $this->campaignHelper->fireWebhook($this->provideSampleConfig(), $this->contact);
    }

    /**
     * @return array<string,mixed>
     */
    private function provideSampleConfig(string $method = 'get', string $type = 'application/x-www-form-urlencoded'): array
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
