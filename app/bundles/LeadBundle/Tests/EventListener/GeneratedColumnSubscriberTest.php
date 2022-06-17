<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Event\GeneratedColumnsEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\EventListener\GeneratedColumnSubscriber;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class GeneratedColumnSubscriberTest extends TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private $translator;

    private GeneratedColumnSubscriber $generatedColumnSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $segmentModel = new class() extends ListModel {
            public function __construct()
            {
            }
        };

        $this->translator                = $this->createMock(TranslatorInterface::class);
        $this->generatedColumnSubscriber = new GeneratedColumnSubscriber($segmentModel, $this->translator);
    }

    public function testInGeneratedColumnsBuild(): void
    {
        $event = new GeneratedColumnsEvent();

        $this->generatedColumnSubscriber->onGeneratedColumnsBuild($event);

        /** @var GeneratedColumn $generatedColumn */
        $generatedColumn = $event->getGeneratedColumns()->current();

        Assert::assertSame(MAUTIC_TABLE_PREFIX.'leads', $generatedColumn->getTableName());
        Assert::assertSame('generated_email_domain', $generatedColumn->getColumnName());
        Assert::assertSame('VARCHAR(255) AS (SUBSTRING(email, LOCATE("@", email) + 1)) COMMENT \'(DC2Type:generated)\'', $generatedColumn->getColumnDefinition());
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

        Assert::assertSame(
            [
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
                'object' => 'lead',
            ],
            $event->getChoices()['lead']['generated_email_domain']
        );
    }
}
