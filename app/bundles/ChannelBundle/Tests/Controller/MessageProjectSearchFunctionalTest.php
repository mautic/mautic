<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Controller;

use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class MessageProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $messageAlpha = $this->createMessage('Message Alpha');
        $messageBeta  = $this->createMessage('Message Beta');
        $this->createMessage('Message Gamma');
        $this->createMessage('Message Delta');

        $messageAlpha->addProject($projectOne);
        $messageAlpha->addProject($projectTwo);
        $messageBeta->addProject($projectTwo);
        $messageBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/messages', '/s/messages']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Message Alpha', 'Message Beta'],
            'unexpectedEntities'  => ['Message Gamma', 'Message Delta'],
        ];

        yield 'search by one project AND message name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Message Beta'],
            'unexpectedEntities'  => ['Message Alpha', 'Message Gamma', 'Message Delta'],
        ];

        yield 'search by one project OR message name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Message Alpha', 'Message Beta', 'Message Gamma'],
            'unexpectedEntities'  => ['Message Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Message Gamma', 'Message Delta'],
            'unexpectedEntities'  => ['Message Alpha', 'Message Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Message Beta'],
            'unexpectedEntities'  => ['Message Alpha', 'Message Gamma', 'Message Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Message Gamma', 'Message Delta'],
            'unexpectedEntities'  => ['Message Alpha', 'Message Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Message Alpha', 'Message Beta'],
            'unexpectedEntities'  => ['Message Gamma', 'Message Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Message Alpha', 'Message Gamma', 'Message Delta'],
            'unexpectedEntities'  => ['Message Beta'],
        ];
    }

    private function createMessage(string $name): Message
    {
        $message = new Message();
        $message->setName($name);
        $message->addChannel((new Channel())
            ->setChannel('email')
            ->setMessage($message));
        $this->em->persist($message);

        return $message;
    }
}
