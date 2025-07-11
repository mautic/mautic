<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\Request;

class FocusAjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testViewsCount(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $focus      = $this->createFocus('popup');
        $focusModel->saveEntity($focus);

        $leads = [
            $this->createLead(),
            $this->createLead(),
        ];

        $focusModel->addStat($focus, Stat::TYPE_NOTIFICATION, null, $leads[0]);
        $focusModel->addStat($focus, Stat::TYPE_NOTIFICATION, null, $leads[0]);
        $focusModel->addStat($focus, Stat::TYPE_NOTIFICATION, null, $leads[1]);

        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/ajax?action=plugin:focus:getViewsCount&focusId={$focus->getId()}");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertSame([
            'success'     => 1,
            'views'       => 3,
            'uniqueViews' => 2,
        ], json_decode($response->getContent(), true));
    }

    public function testClickThroughCount(): void
    {
        /** @var FocusModel $focusModel */
        $focusModel = static::getContainer()->get('mautic.focus.model.focus');
        $focus      = $this->createFocus('popup');
        $focusModel->saveEntity($focus);

        $lead1 = $this->createLead();
        $lead2 = $this->createLead();

        $focusModel->addStat($focus, Stat::TYPE_CLICK, $this->createHit($lead1), $lead1);
        $focusModel->addStat($focus, Stat::TYPE_CLICK, $this->createHit($lead1), $lead1);
        $focusModel->addStat($focus, Stat::TYPE_CLICK, $this->createHit($lead2), $lead2);

        $this->client->xmlHttpRequest(Request::METHOD_GET, "/s/ajax?action=plugin:focus:getClickThroughCount&focusId={$focus->getId()}");
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertSame([
            'success'      => 1,
            'clickThrough' => 2,
        ], json_decode($response->getContent(), true));
    }

    private function createHit(Lead $lead): Hit
    {
        $hit = new Hit();
        $hit->setLead($lead);

        return $hit;
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
