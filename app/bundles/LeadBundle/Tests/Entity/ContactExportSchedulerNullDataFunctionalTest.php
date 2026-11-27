<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use PHPUnit\Framework\Attributes\DataProvider;

final class ContactExportSchedulerNullDataFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Checks that a scheduled contact export whose data column holds a null value loads with empty data.
     *
     * @param string|null $storedValue the raw value written to the data column
     */
    #[DataProvider('provideNullValues')]
    public function testSchedulerWithNullDataLoads(?string $storedValue): void
    {
        $scheduler = new ContactExportScheduler();
        $scheduler->setScheduledDateTime(new \DateTimeImmutable());
        $scheduler->setData(['filename' => 'contacts.csv']);
        $this->em->persist($scheduler);
        $this->em->flush();

        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'contact_export_scheduler SET data = :value WHERE id = :id',
            ['value' => $storedValue, 'id' => $scheduler->getId()]
        );
        $this->em->clear();

        $loaded = $this->em->find(ContactExportScheduler::class, $scheduler->getId());

        $this->assertInstanceOf(ContactExportScheduler::class, $loaded);
        $this->assertSame([], $loaded->getData());
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
