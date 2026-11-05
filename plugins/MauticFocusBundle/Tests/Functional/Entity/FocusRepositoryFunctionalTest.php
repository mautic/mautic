<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\Attributes\DataProvider;

class FocusRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private FocusModel $focusModel;
    private FocusRepository $focusRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusModel      = static::$kernel->getContainer()->get('mautic.focus.model.focus');
        $this->focusRepository = $this->focusModel->getRepository();
        $translator            = static::$kernel->getContainer()->get('translator');
        $reflection            = new \ReflectionClass($this->focusRepository);
        if ($reflection->hasProperty('translator')) {
            $prop = $reflection->getProperty('translator');
            $prop->setValue($this->focusRepository, $translator);
        }
        $this->setUpTestData();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function styleCommandProvider(): iterable
    {
        yield 'bar' => ['style:bar', 'bar'];
        yield 'modal' => ['style:modal', 'modal'];
        yield 'notification' => ['style:notification', 'notification'];
        yield 'fullpage' => ['style:fullpage', 'page'];
    }

    #[DataProvider('styleCommandProvider')]
    public function testStyleCommandReturnsOnlyFocusesWithThatStyle(string $command, string $style): void
    {
        $result = $this->getSearchResult($this->createFilter($command));

        $this->assertNotEmpty($result);
        foreach ($result as $focus) {
            $this->assertSame($style, $focus->getStyle());
        }
    }

    public function testNegatedStyleCommandExcludesThatStyle(): void
    {
        $result = $this->getSearchResult($this->createFilter('style:bar', true));

        $this->assertNotEmpty($result);
        foreach ($result as $focus) {
            $this->assertNotSame('bar', $focus->getStyle());
        }
    }

    public function testSearchCommandWhereClauseWithStandardCommand(): void
    {
        $published = $this->createFocus('Published Focus', 'bar');
        $published->setIsPublished(true);
        $this->focusModel->saveEntity($published);

        $unpublished = $this->createFocus('Unpublished Focus', 'bar');
        $unpublished->setIsPublished(false);
        $this->focusModel->saveEntity($unpublished);

        $ids = array_map(
            static fn (Focus $focus): ?int => $focus->getId(),
            $this->getSearchResult($this->createFilter('is:published'))
        );

        $this->assertContains($published->getId(), $ids);
        $this->assertNotContains($unpublished->getId(), $ids);
    }

    public function testGetSearchCommands(): void
    {
        $commands = $this->focusRepository->getSearchCommands();

        $this->assertIsArray($commands);
        $this->assertContains('mautic.focus.focus.searchcommand.stylebar', $commands);
        $this->assertContains('mautic.focus.focus.searchcommand.stylemodal', $commands);
        $this->assertContains('mautic.focus.focus.searchcommand.stylenotification', $commands);
        $this->assertContains('mautic.focus.focus.searchcommand.stylefullpage', $commands);
        $this->assertContains('mautic.project.searchcommand.name', $commands);
    }

    private function setUpTestData(): void
    {
        $focusBar          = $this->createFocus('Bar Focus', 'bar');
        $focusModal        = $this->createFocus('Modal Focus', 'modal');
        $focusNotification = $this->createFocus('Notification Focus', 'notification');
        $focusFullpage     = $this->createFocus('Fullpage Focus', 'page');

        $this->focusModel->saveEntity($focusBar);
        $this->focusModel->saveEntity($focusModal);
        $this->focusModel->saveEntity($focusNotification);
        $this->focusModel->saveEntity($focusFullpage);
    }

    private function createFocus(string $name, string $style): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('link');
        $focus->setStyle($style);
        $focus->setProperties([
            'bar' => [
                'allow_hide' => 1,
                'push_page'  => 1,
                'sticky'     => 1,
                'size'       => 'large',
                'placement'  => 'top',
            ],
            'modal' => [
                'placement' => 'top',
            ],
            'notification' => [
                'placement' => 'top_left',
            ],
            'page'            => [],
            'animate'         => 0,
            'link_activation' => 1,
            'colors'          => [
                'primary'     => '4e5d9d',
                'text'        => '000000',
                'button'      => 'fdb933',
                'button_text' => 'ffffff',
            ],
            'content' => [
                'headline'        => null,
                'tagline'         => null,
                'link_text'       => null,
                'link_url'        => null,
                'link_new_window' => 1,
                'font'            => 'Arial, Helvetica, sans-serif',
                'css'             => null,
            ],
            'when'                  => 'immediately',
            'timeout'               => null,
            'frequency'             => 'everypage',
            'stop_after_conversion' => 1,
        ]);

        return $focus;
    }

    private function createFilter(string $command, bool $not = false): \stdClass
    {
        $filter          = new \stdClass();
        $filter->command = $command;
        $filter->string  = 'test';
        $filter->not     = $not;
        $filter->strict  = false;

        return $filter;
    }

    private function getSearchResult(\stdClass $filter): array
    {
        $q                   = $this->focusRepository->createQueryBuilder('f');
        [$expr, $parameters] = $this->callProtectedMethod('addSearchCommandWhereClause', [$q, $filter]);

        $q->where($expr);
        foreach ($parameters as $param => $value) {
            $q->setParameter($param, $value);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param array<int, mixed> $args
     */
    private function callProtectedMethod(string $methodName, array $args): mixed
    {
        $reflection = new \ReflectionClass($this->focusRepository);
        $method     = $reflection->getMethod($methodName);

        return $method->invokeArgs($this->focusRepository, $args);
    }
}
