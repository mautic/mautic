<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;

class FocusModelFunctionalTest extends MauticMysqlTestCase
{
    private Lead $lead;

    private FocusModel $focusModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $this->lead       = $this->createLead();
    }

    public function testGetStats(): void
    {
        $focusPopupA       = $this->createFocus('popup focus A');
        $focusStatExpected = $this->setTestsData($this->lead, $focusPopupA);

        $to   = new \DateTime('+1 day');
        $from = new \DateTime('-1 month');

        $focusStat = $this->focusModel->getStats($focusPopupA, null, $from, $to);

        $focusViewsCount = array_sum($focusStat['datasets'][0]['data']);
        $focusClickCount = array_sum($focusStat['datasets'][1]['data']);

        $this->assertEquals($focusStatExpected['view'], $focusViewsCount);
        $this->assertEquals($focusStatExpected['click'], $focusClickCount);
    }

    /**
     * @return array<string, int>
     */
    private function setTestsData(Lead $lead, Focus $focus): array
    {
        $hitPopupA = new Hit();
        $hitPopupA->setLead($lead);

        $this->focusModel->addStat($focus, Stat::TYPE_NOTIFICATION, null, $lead);
        $this->focusModel->addStat($focus, Stat::TYPE_CLICK, $hitPopupA, $lead);
        $this->focusModel->addStat($focus, Stat::TYPE_CLICK, $hitPopupA, $lead);
        $this->focusModel->addStat($focus, Stat::TYPE_CLICK, $hitPopupA, $lead);
        $this->focusModel->addStat($focus, Stat::TYPE_CLICK, $hitPopupA, $lead);

        return ['view' => 1, 'click' => 4];
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

        $this->focusModel->saveEntity($focus);

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
