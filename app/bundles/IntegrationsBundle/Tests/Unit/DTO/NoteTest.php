<?php

declare(strict_types=1);

use Mautic\IntegrationsBundle\DTO\Note;
use PHPUnit\Framework\TestCase;

final class NoteTest extends TestCase
{
    public function testGetterFunctions(): void
    {
        $note = 'This is note';
        $type = Note::TYPE_ALERT;

        $noteObject = new Note($note, $type);

        $this->assertSame($note, $noteObject->getNote());
        $this->assertSame($type, $noteObject->getType());
    }

    public function testGetterFunctionsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Type value can be either "%s" or "%s".', Note::TYPE_INFO, Note::TYPE_ALERT));

        $noteObject = new Note('Notes', 'randomType');
    }
}
