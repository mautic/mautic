<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

final class MaxMindDoNotSellPurgeCommandTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        file_put_contents(sys_get_temp_dir().'/do_not_sell_test.json', json_encode([
            'exclusions' => [
                [
                    'exclusion_type' => 'ccpa_do_not_sell',
                    'data_type'      => 'network',
                    'value'          => '9.9.9.9/32',
                ],
            ],
        ]));

        $this->configParams['maxmind_do_not_sell_list_path'] = sys_get_temp_dir().'/do_not_sell_test.json';

        parent::setUp();
    }

    public function testLoadedList3IsReached(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);

        $ip = new IpAddress();
        $ip->setIpAddress('9.9.9.9');
        $this->em->persist($ip);

        $lead->addIpAddress($ip);
        $this->em->flush();

        $lead->getIpAddresses()->clear();

        $tester = $this->testSymfonyCommand('mautic:max-mind:purge');

        $this->assertSame(0, $tester->getStatusCode());

        $this->assertStringContainsString('Found 1 contacts', $tester->getDisplay());
    }
}
