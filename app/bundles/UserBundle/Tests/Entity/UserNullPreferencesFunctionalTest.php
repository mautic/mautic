<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class UserNullPreferencesFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Checks that a user whose preferences column holds a null value loads with empty preferences.
     *
     * @param string|null $storedValue the raw value written to the preferences column
     */
    #[DataProvider('provideNullValues')]
    public function testUserWithNullPreferencesLoads(?string $storedValue): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertInstanceOf(User::class, $user);

        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'users SET preferences = :value WHERE id = :id',
            ['value' => $storedValue, 'id' => $user->getId()]
        );
        $this->em->clear();

        $loaded = $this->em->find(User::class, $user->getId());

        $this->assertInstanceOf(User::class, $loaded);
        $this->assertSame([], $loaded->getPreferences());
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
