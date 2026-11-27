<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Entity;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;

final class MessageQueueNullOptionsFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Checks that a queued message whose options column holds a null value loads with empty options and metadata.
     *
     * @param string|null $storedValue the raw value written to the options column
     */
    #[DataProvider('provideNullValues')]
    public function testMessageWithNullOptionsLoads(?string $storedValue): void
    {
        $contact = new Lead();
        $this->em->persist($contact);

        $message = new MessageQueue();
        $message->setChannel('email');
        $message->setChannelId(1);
        $message->setLead($contact);
        $message->setPriority(MessageQueue::PRIORITY_NORMAL);
        $message->setMaxAttempts(3);
        $message->setDatePublished(new \DateTime());
        $this->em->persist($message);
        $this->em->flush();

        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'message_queue SET options = :value WHERE id = :id',
            ['value' => $storedValue, 'id' => $message->getId()]
        );
        $this->em->clear();

        $loaded = $this->em->find(MessageQueue::class, $message->getId());

        $this->assertInstanceOf(MessageQueue::class, $loaded);
        $this->assertSame([], $loaded->getOptions());
        $this->assertSame([], $loaded->getMetadata());
    }

    /**
     * Lists the stored values that read back as null.
     *
     * @return iterable<string, array{string|null}> the SQL NULL and the serialized null
     */
    public static function provideNullValues(): iterable
    {
        yield 'SQL NULL' => [null];
        yield 'serialized null' => ['N;'];
    }
}
