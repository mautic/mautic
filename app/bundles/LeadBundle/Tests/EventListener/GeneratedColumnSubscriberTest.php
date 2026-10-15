<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GeneratedColumnSubscriberTest extends TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private \Mautic\CoreBundle\Translation\Translator|MockObject $translator;

    private GeneratedColumnSubscriber $generatedColumnSubscriber;

    private ListModel $segmentModel;

    protected function setUp(): void
    {
        parent::setUp();
        $modelTranslator = $this->createMock(Translator::class);
        $modelTranslator
            ->method('trans')
            ->willReturnArgument(0);

        $this->segmentModel = new class($modelTranslator) extends ListModel {
            public function __construct(Translator $translator)
            {
                $this->translator = $translator;
            }
        };

        $this->translator                = $this->createMock(TranslatorInterface::class);
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $this->generatedColumnSubscriber = new GeneratedColumnSubscriber($this->segmentModel, $this->translator, $connection);
    }

    public function testInGeneratedColumnsBuild(): void
    {
        $event = new GeneratedColumnsEvent();

        $this->generatedColumnSubscriber->onGeneratedColumnsBuild($event);

        $generatedColumn = $event->getGeneratedColumns()->current();

        $this->assertSame(MAUTIC_TABLE_PREFIX.'leads', $generatedColumn->getTableName());
        $this->assertSame('generated_email_domain', $generatedColumn->getColumnName());
        $this->assertSame("VARCHAR(255) AS (SUBSTRING(email, POSITION('@' IN email) + 1)) COMMENT '(DC2Type:generated)'", $generatedColumn->getColumnDefinition());
    }

    public function testPostgreSqlGeneratedExpressionUsesDeterministicCollationForPosition(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new PostgreSQLPlatform());
        $subscriber = new GeneratedColumnSubscriber($this->segmentModel, $this->translator, $connection);
        $event = new GeneratedColumnsEvent();

        $subscriber->onGeneratedColumnsBuild($event);

        $this->assertSame(
            "VARCHAR(255) COLLATE mautic_unicode_ci GENERATED ALWAYS AS (SUBSTRING(email, POSITION('@' IN (email COLLATE \"C\")) + 1)) STORED",
            $event->getGeneratedColumns()->current()->getColumnDefinition(new PostgreSQLPlatform())
        );
    }

    public function testOnGenerateSegmentFilters(): void
    {
        $event = new LeadListFiltersChoicesEvent(
            [],
            [],
            $this->translator,
            new Request()
        );

        $this->translator->method('trans')
            ->with('mautic.email.segment.choice.generated_email_domain')
            ->willReturn('translated string');

        $this->generatedColumnSubscriber->onGenerateSegmentFilters($event);

        $this->assertSame([
            'label'      => 'translated string',
            'properties' => ['type' => 'text'],
            'operators'  => [
                'mautic.lead.list.form.operator.equals'     => '=',
                'mautic.lead.list.form.operator.notequals'  => '!=',
                'mautic.lead.list.form.operator.isempty'    => 'empty',
                'mautic.lead.list.form.operator.isnotempty' => '!empty',
                'mautic.lead.list.form.operator.islike'     => 'like',
                'mautic.lead.list.form.operator.isnotlike'  => '!like',
                'mautic.lead.list.form.operator.regexp'     => 'regexp',
                'mautic.lead.list.form.operator.notregexp'  => '!regexp',
                'mautic.core.operator.starts.with'          => 'startsWith',
                'mautic.core.operator.ends.with'            => 'endsWith',
                'mautic.core.operator.contains'             => 'contains',
            ],
            'object'    => 'lead',
            'iconClass' => 'ri-at-line',
        ], $event->getChoices()['lead']['generated_email_domain']);
    }
}
