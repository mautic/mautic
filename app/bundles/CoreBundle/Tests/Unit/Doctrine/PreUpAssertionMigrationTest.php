<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PreUpAssertionMigrationTest extends TestCase
{
    public function testPreUpWithoutSkipAssertions(): void
    {
        $migration = new class() extends PreUpAssertionMigration {
            /**
             * @var array<string>
             */
            public array $messages = [];

            /**
             * @noinspection PhpMissingParentConstructorInspection
             */
            public function __construct()
            {
            }

            protected function preUpAssertions(): void
            {
            }

            protected function write(string $message): void
            {
                $this->messages[] = $message;
            }
        };

        $migration->preUp($this->createMock(Schema::class));

        Assert::assertEmpty($migration->messages);
    }

    public function testPreUpSkipped(): void
    {
        $migration = new class() extends PreUpAssertionMigration {
            /**
             * @var array<string>
             */
            public array $messages = [];

            /**
             * @noinspection PhpMissingParentConstructorInspection
             */
            public function __construct()
            {
            }

            protected function preUpAssertions(): void
            {
                $this->skipAssertion(function (Schema $schema) {
                    return true;
                }, 'First exists');

                $this->skipAssertion(function (Schema $schema) {
                    return true;
                }, 'Second exists');

                $this->skipAssertion(function (Schema $schema) {
                    return true;
                }, 'Third exists');
            }

            protected function write(string $message): void
            {
                $this->messages[] = $message;
            }
        };

        try {
            $migration->preUp($this->createMock(Schema::class));
            $this->fail(sprintf('Exception %s should have been thrown', SkipMigration::class));
        } catch (SkipMigration) {
        }

        Assert::assertCount(3, $migration->messages);
        Assert::assertSame([
            '<comment>First exists</comment>',
            '<comment>Second exists</comment>',
            '<comment>Third exists</comment>',
        ], $migration->messages);
    }

    public function testPreUpNotSkipped(): void
    {
        $migration = new class() extends PreUpAssertionMigration {
            /**
             * @var array<string>
             */
            public array $messages = [];

            /**
             * @noinspection PhpMissingParentConstructorInspection
             */
            public function __construct()
            {
            }

            protected function preUpAssertions(): void
            {
                $this->skipAssertion(function (Schema $schema) {
                    return true;
                }, 'First exists');

                $this->skipAssertion(function (Schema $schema) {
                    return true;
                }, 'Second exists');

                $this->skipAssertion(function (Schema $schema) {
                    return false;
                }, 'Third does not exist');
            }

            protected function write(string $message): void
            {
                $this->messages[] = $message;
            }
        };

        $migration->preUp($this->createMock(Schema::class));

        Assert::assertCount(2, $migration->messages);
        Assert::assertSame([
            '<comment>First exists</comment>',
            '<comment>Second exists</comment>',
        ], $migration->messages);
    }
}
