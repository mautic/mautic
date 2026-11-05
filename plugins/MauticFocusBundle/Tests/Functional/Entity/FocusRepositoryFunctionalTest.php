<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class FocusRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private FocusModel $focusModel;
    private FocusRepository $focusRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusModel      = self::getContainer()->get(FocusModel::class);
        $this->focusRepository = $this->focusModel->getRepository();
        $this->setUpTestData();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function styleCommandProvider(): iterable
    {
        yield 'bar' => ['style:bar', 'Bar Focus'];
        yield 'modal' => ['style:modal', 'Modal Focus'];
        yield 'notification' => ['style:notification', 'Notification Focus'];
        yield 'fullpage' => ['style:fullpage', 'Fullpage Focus'];
    }

    #[DataProvider('styleCommandProvider')]
    public function testStyleCommandReturnsOnlyFocusesWithThatStyle(string $searchString, string $expectedName): void
    {
        $this->assertSame([$expectedName], $this->getFocusNames($searchString));
    }

    public function testNegatedStyleCommandExcludesThatStyle(): void
    {
        $this->assertSame(
            ['Fullpage Focus', 'Modal Focus', 'Notification Focus'],
            $this->getFocusNames('!style:bar')
        );
    }

    public function testIsPublishedCommandReturnsOnlyPublishedFocuses(): void
    {
        $published = $this->createFocus('Published Focus', 'bar');
        $published->setIsPublished(true);
        $this->focusModel->saveEntity($published);

        $unpublished = $this->createFocus('Unpublished Focus', 'bar');
        $unpublished->setIsPublished(false);
        $this->focusModel->saveEntity($unpublished);

        $ids = array_map(
            static fn (Focus $focus): ?int => $focus->getId(),
            $this->getSearchResult('is:published')
        );

        $this->assertContains($published->getId(), $ids);
        $this->assertNotContains($unpublished->getId(), $ids);
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

    /**
     * @return Focus[]
     */
    private function getSearchResult(string $searchString): array
    {
        return $this->focusRepository->getEntities([
            'filter'           => ['string' => $searchString, 'force' => []],
            'ignore_paginator' => true,
        ]);
    }

    /**
     * @return string[]
     */
    private function getFocusNames(string $searchString): array
    {
        $names = array_map(
            static fn (Focus $focus): string => $focus->getName(),
            $this->getSearchResult($searchString)
        );
        sort($names);

        return $names;
    }
}
