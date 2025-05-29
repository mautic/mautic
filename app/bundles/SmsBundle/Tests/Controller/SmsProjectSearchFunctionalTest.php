<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;
use Mautic\SmsBundle\Entity\Sms;

final class SmsProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $smsAlpha = $this->createSms('Sms Alpha');
        $smsBeta  = $this->createSms('Sms Beta');
        $this->createSms('Sms Gamma');
        $this->createSms('Sms Delta');

        $smsAlpha->addProject($projectOne);
        $smsAlpha->addProject($projectTwo);
        $smsBeta->addProject($projectTwo);
        $smsBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/smses', '/s/sms']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Sms Alpha', 'Sms Beta'],
            'unexpectedEntities'  => ['Sms Gamma', 'Sms Delta'],
        ];

        yield 'search by one project AND sms name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Sms Beta'],
            'unexpectedEntities'  => ['Sms Alpha', 'Sms Gamma', 'Sms Delta'],
        ];

        yield 'search by one project OR sms name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Sms Alpha', 'Sms Beta', 'Sms Gamma'],
            'unexpectedEntities'  => ['Sms Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Sms Gamma', 'Sms Delta'],
            'unexpectedEntities'  => ['Sms Alpha', 'Sms Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Sms Beta'],
            'unexpectedEntities'  => ['Sms Alpha', 'Sms Gamma', 'Sms Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Sms Gamma', 'Sms Delta'],
            'unexpectedEntities'  => ['Sms Alpha', 'Sms Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Sms Alpha', 'Sms Beta'],
            'unexpectedEntities'  => ['Sms Gamma', 'Sms Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Sms Alpha', 'Sms Gamma', 'Sms Delta'],
            'unexpectedEntities'  => ['Sms Beta'],
        ];
    }

    private function createSms(string $name): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage('Message for '.$name);
        $this->em->persist($sms);

        return $sms;
    }
}
