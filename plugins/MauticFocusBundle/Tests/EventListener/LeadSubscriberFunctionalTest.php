<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

class LeadSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private Lead $lead;

    private FocusModel $focusModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $this->lead       = $this->createLead();

        $this->setTestsData($this->lead, $this->focusModel);
    }

    public function testSearchPhraseInNameFocusStat(): void
    {
        $this->assertCount(3, $this->searchPhrase('bar', $this->lead, $this->focusModel));
        $this->assertCount(4, $this->searchPhrase('popup', $this->lead, $this->focusModel));
        $this->assertCount(2, $this->searchPhrase('popup focus B', $this->lead, $this->focusModel));
    }

    public function testSearchPhraseInTypeFocusStat(): void
    {
        $this->assertCount(2, $this->searchPhrase('click', $this->lead, $this->focusModel));
        $this->assertCount(5, $this->searchPhrase('view', $this->lead, $this->focusModel));
    }

    /**
     * @return array<string, mixed>
     */
    private function searchPhrase(string $phrase, Lead $lead, FocusModel $focusModel): array
    {
        $searchViewStats  = $focusModel->getStatRepository()->getStatsViewByLead((int) $lead->getId(), ['search'=>$phrase]);
        $searchClickStats = $focusModel->getStatRepository()->getStatsClickByLead((int) $lead->getId(), ['search'=>$phrase]);

        return array_merge($searchViewStats, $searchClickStats);
    }

    private function setTestsData(Lead $lead, FocusModel $focusModel): void
    {
        $focusPopupA = $this->createFocus('popup focus A');
        $focusPopupB = $this->createFocus('popup focus B');
        $focusPopupC = $this->createFocus('popup focus C');

        $focusBarA   = $this->createFocus('bar focus A');
        $focusBarB   = $this->createFocus('bar focus B');

        $this->focusModel->saveEntity($focusPopupA);
        $this->focusModel->saveEntity($focusPopupB);
        $this->focusModel->saveEntity($focusPopupC);

        $this->focusModel->saveEntity($focusBarA);
        $this->focusModel->saveEntity($focusBarB);

        $hitPopupA = new Hit();
        $hitPopupA->setLead($lead);

        $hitBarB = new Hit();
        $hitBarB->setLead($lead);

        $this->focusModel->addStat($focusPopupA, Stat::TYPE_NOTIFICATION, null, $lead);
        $this->focusModel->addStat($focusPopupB, Stat::TYPE_NOTIFICATION, null, $lead);
        $this->focusModel->addStat($focusPopupB, Stat::TYPE_CLICK, $hitPopupA, $lead);
        $this->focusModel->addStat($focusPopupC, Stat::TYPE_NOTIFICATION, null, $lead);

        $this->focusModel->addStat($focusBarA, Stat::TYPE_NOTIFICATION, null, $lead);
        $this->focusModel->addStat($focusBarA, Stat::TYPE_CLICK, $hitBarB, $lead);
        $this->focusModel->addStat($focusBarB, Stat::TYPE_NOTIFICATION, null, $lead);
    }

    private function createFocus(string $name): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('link');
        $focus->setStyle('modal');
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

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Contact');
        $lead->setEmail('test@test.com');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }
}
