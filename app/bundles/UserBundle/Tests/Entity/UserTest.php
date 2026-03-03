<?php

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setUsername('testUser');
        $user->setPlainPassword('plainPass');
        $user->setCurrentPassword('currentPass');

        $user = unserialize(serialize($user));
        \assert($user instanceof User);

        $this->assertSame('testUser', $user->getUsername());
        $this->assertNull($user->getPlainPassword());
        $this->assertNull($user->getCurrentPassword());
    }

    public function testUserIsGuest(): void
    {
        $user = new User(true);
        $this->assertTrue($user->isGuest());
    }

    public function testUserIsNotGuest(): void
    {
        $user = new User();
        $this->assertFalse($user->isGuest());
    }

    public function testUserSerializationContainsEssentialData(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setIsPublished(true);

        $serializedData = $user->__serialize();

        $this->assertIsArray($serializedData);
        $this->assertCount(4, $serializedData);
        $this->assertSame($user->getId(), $serializedData[0]);
        $this->assertSame('testuser', $serializedData[1]);
        $this->assertSame('hashedpassword', $serializedData[2]);
        $this->assertTrue($serializedData[3]); // isPublished
    }

    public function testUserUnserializationRestoresEssentialData(): void
    {
        $originalUser = new User();
        $originalUser->setUsername('testuser');
        $originalUser->setPassword('hashedpassword');
        $originalUser->setIsPublished(true);

        $serializedData = $originalUser->__serialize();

        $newUser = new User();
        $newUser->__unserialize($serializedData);

        $this->assertSame($originalUser->getId(), $newUser->getId());
        $this->assertSame($originalUser->getUsername(), $newUser->getUsername());
        $this->assertSame($originalUser->getPassword(), $newUser->getPassword());
        $this->assertSame($originalUser->isPublished(), $newUser->isPublished());
    }

    public function testSerializationRoundTrip(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setIsPublished(true);

        // Test round-trip with __serialize/__unserialize
        $serializedData = $user->__serialize();
        $newUser        = new User();
        $newUser->__unserialize($serializedData);

        $this->assertSame($user->getId(), $newUser->getId());
        $this->assertSame($user->getUsername(), $newUser->getUsername());
        $this->assertSame($user->getPassword(), $newUser->getPassword());
        $this->assertSame($user->isPublished(), $newUser->isPublished());
    }

    public function testSerializationExcludesNonEssentialData(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('hashedpassword');
        $user->setIsPublished(true);
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('test@example.com');
        $user->setPosition('Developer');
        $user->setTimezone('UTC');
        $user->setLocale('en');
        $user->setSignature('Test signature');

        $serializedData = $user->__serialize();

        // Only 4 essential fields should be serialized
        $this->assertCount(4, $serializedData);

        // The serialized data is an indexed array, not associative
        // So we verify it contains only the expected values in the expected order
        $this->assertSame($user->getId(), $serializedData[0]);
        $this->assertSame('testuser', $serializedData[1]);
        $this->assertSame('hashedpassword', $serializedData[2]);
        $this->assertTrue($serializedData[3]);
    }
}
