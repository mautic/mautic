<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class AssignObjectPropertyTest
{
    private \stdClass $repositoryMock;

    private string $tableName;

    public function testObjectProperty(): void
    {
        $repository = $this->repositoryMock;
    }

    public function testScalarProperty(): void
    {
        $tableName = $this->tableName;
    }
}
