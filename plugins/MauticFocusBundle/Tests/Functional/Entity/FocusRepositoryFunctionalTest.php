<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use stdClass;

class FocusRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private FocusModel $focusModel;
    private FocusRepository $focusRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusModel = static::$kernel->getContainer()->get('mautic.focus.model.focus');
        $this->focusRepository = $this->focusModel->getRepository();
        $this->setUpTestData();
    }

    public function testSearchCommandWhereClauseWithStyleBar(): void
    {
        $filter = $this->createFilter('style:bar');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('bar', $parameters['param1']);
    }

    public function testSearchCommandWhereClauseWithStyleModal(): void
    {
        $filter = $this->createFilter('style:modal');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('modal', $parameters['param1']);
    }

    public function testSearchCommandWhereClauseWithStyleNotification(): void
    {
        $filter = $this->createFilter('style:notification');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('notification', $parameters['param1']);
    }

    public function testSearchCommandWhereClauseWithStyleFullpage(): void
    {
        $filter = $this->createFilter('style:fullpage');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('page', $parameters['param1']);
    }

    public function testSearchCommandWhereClauseWithNotFilter(): void
    {
        $filter = $this->createFilter('style:bar', true);
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('bar', $parameters['param1']);
    }

    public function testSearchCommandWhereClauseWithStandardCommand(): void
    {
        $filter = $this->createFilter('ispublished:1');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
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

    public function testGetTableAlias(): void
    {
        $this->assertEquals('f', $this->focusRepository->getTableAlias());
    }

    public function testGetDefaultOrder(): void
    {
        $order = $this->focusRepository->getDefaultOrder();

        $this->assertIsArray($order);
        $this->assertCount(1, $order);
        $this->assertCount(2, $order[0]);
        $this->assertEquals('f.name', $order[0][0]);
        $this->assertEquals('ASC', $order[0][1]);
    }

    public function testAddCatchAllWhereClause(): void
    {
        $filter = $this->createFilter('test');
        $q = $this->focusRepository->createQueryBuilder('f');

        [$expr, $parameters] = $this->focusRepository->addCatchAllWhereClause($q, $filter);

        $this->assertNotNull($expr);
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('param1', $parameters);
        $this->assertEquals('%test%', $parameters['param1']);
    }

    public function testGetEntities(): void
    {
        $args = ['iterable_mode' => false];
        $entities = $this->focusRepository->getEntities($args);

        $this->assertIsArray($entities);
        $this->assertGreaterThan(0, count($entities));
    }

    public function testGetEntitiesWithIterableMode(): void
    {
        $args = ['iterable_mode' => true];
        $entities = $this->focusRepository->getEntities($args);

        $this->assertIsArray($entities);
        $this->assertGreaterThan(0, count($entities));
    }

    public function testFindByForm(): void
    {
        $focuses = $this->focusRepository->findByForm(null);

        $this->assertIsArray($focuses);
    }

    public function testGetFocusList(): void
    {
        $focusList = $this->focusRepository->getFocusList(1);

        $this->assertIsArray($focusList);
        $this->assertGreaterThan(0, count($focusList));
    }

    public function testSearchCommandIntegration(): void
    {
        $q = $this->focusRepository->createQueryBuilder('f');

        $filter = $this->createFilter('style:bar');
        [$expr, $parameters] = $this->focusRepository->addSearchCommandWhereClause($q, $filter);

        $q->where($expr);
        foreach ($parameters as $param => $value) {
            $q->setParameter($param, $value);
        }

        $result = $q->getQuery()->getResult();

        $this->assertIsArray($result);
        foreach ($result as $focus) {
            $this->assertInstanceOf(Focus::class, $focus);
            $this->assertEquals('bar', $focus->getStyle());
        }
    }

    private function setUpTestData(): void
    {
        $focusBar = $this->createFocus('Bar Focus', 'bar');
        $focusModal = $this->createFocus('Modal Focus', 'modal');
        $focusNotification = $this->createFocus('Notification Focus', 'notification');
        $focusFullpage = $this->createFocus('Fullpage Focus', 'page');

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

    private function createFilter(string $command, bool $not = false): stdClass
    {
        $filter = new stdClass();
        $filter->command = $command;
        $filter->string = 'test';
        $filter->not = $not;
        $filter->strict = false;
        return $filter;
    }
}
