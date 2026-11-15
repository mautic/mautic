<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoUselessAssertInstanceOf;

use PHPUnit\Framework\TestCase;

final class UselessAssertInstanceOf extends TestCase
{
    public function testUseless(): void
    {
        $service = $this->getService(\ArrayObject::class);
        $this->assertInstanceOf(\ArrayObject::class, $service);

        self::assertInstanceOf(\Countable::class, $service);
    }

    public function testSkipNarrowing(?\ArrayObject $maybe, object $object): void
    {
        $this->assertInstanceOf(\ArrayObject::class, $maybe);
        $this->assertInstanceOf(\ArrayObject::class, $object);
        $this->assertInstanceOf(\ArrayObject::class, $this->getService(\Countable::class));
    }

    /**
     * @template TService of object
     *
     * @param class-string<TService> $serviceId
     *
     * @return TService
     */
    private function getService(string $serviceId): object
    {
        return new $serviceId();
    }
}
