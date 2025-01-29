<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Integration;

use Mautic\IntegrationsBundle\DTO\Note;
use Mautic\IntegrationsBundle\Integration\ConfigFormNotesTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormNotesInterface;
use PHPUnit\Framework\TestCase;

class ConfigFormNotesTraitTest extends TestCase
{
    public function testConfigFormNotesTraitFormDefaultValues(): void
    {
        $configFormNotes = new class implements ConfigFormNotesInterface {
            use ConfigFormNotesTrait;
        };

        $this->assertNull($configFormNotes->getAuthorizationNote());
        $this->assertNull($configFormNotes->getFeaturesNote());
        $this->assertNull($configFormNotes->getFieldMappingNote());
    }

    public function testConfigFormNotesTraitFormForCustomValues(): void
    {
        $configFormNotes = new class implements ConfigFormNotesInterface {
            use ConfigFormNotesTrait;

            public function getAuthorizationNote(): Note
            {
                return new Note('Authorisation', Note::TYPE_WARNING);
            }

            public function getFeaturesNote(): Note
            {
                return new Note('Features', Note::TYPE_INFO);
            }

            public function getFieldMappingNote(): Note
            {
                return new Note('Field Mapping', Note::TYPE_WARNING);
            }
        };

        $this->assertInstanceOf(Note::class, $configFormNotes->getAuthorizationNote());
        $this->assertSame(Note::TYPE_WARNING, $configFormNotes->getAuthorizationNote()->getType());
        $this->assertSame('Authorisation', $configFormNotes->getAuthorizationNote()->getNote());

        $this->assertInstanceOf(Note::class, $configFormNotes->getFeaturesNote());
        $this->assertSame(Note::TYPE_INFO, $configFormNotes->getFeaturesNote()->getType());
        $this->assertSame('Features', $configFormNotes->getFeaturesNote()->getNote());

        $this->assertInstanceOf(Note::class, $configFormNotes->getFieldMappingNote());
        $this->assertSame(Note::TYPE_WARNING, $configFormNotes->getFieldMappingNote()->getType());
        $this->assertSame('Field Mapping', $configFormNotes->getFieldMappingNote()->getNote());
    }
}
