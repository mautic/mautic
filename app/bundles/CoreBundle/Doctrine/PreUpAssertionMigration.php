<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;

abstract class PreUpAssertionMigration extends AbstractMauticMigration
{
    /**
     * @var array<int, array<string>>
     */
    private array $skipAssertions = [];

    /**
     * Implement this method to add skip assertions via `PreUpAssertionMigration::skipAssertion()`.
     */
    abstract protected function preUpAssertions(): void;

    /**
     * A template method that addresses partially executed migrations.
     * It skips the migration only if all of skip assertions return true.
     */
    final public function preUp(Schema $schema): void
    {
        $this->preUpAssertions();

        if (!$this->skipAssertions) {
            // there are no assertions to run
            return;
        }

        foreach ($this->skipAssertions as $skipAssertion) {
            if ($skipAssertion['assertion']($schema)) {
                $this->write(sprintf('<comment>%s</comment>', $skipAssertion['message']));
            } else {
                // the migration should not be skipped once there is a failing skip assertion
                return;
            }
        }

        throw new SkipMigration('Schema includes this migration');
    }

    final protected function skipAssertion(callable $assertion, string $message): void
    {
        $this->skipAssertions[] = [
            'assertion' => $assertion,
            'message'   => $message,
        ];
    }
}
