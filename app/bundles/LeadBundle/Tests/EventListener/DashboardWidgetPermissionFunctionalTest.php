<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\DashboardWidgetPermissionTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;

/**
 * The contacts widgets must honour lead:leads:viewother.
 *
 * These restrict by adding an owner_id filter, where the form widgets join the form's
 * created_by, so the two cover the two halves of the same defect.
 */
final class DashboardWidgetPermissionFunctionalTest extends MauticMysqlTestCase
{
    use DashboardWidgetPermissionTrait;

    private const string OWN_CONTACT   = 'widget-own@mautic-test.com';
    private const string OTHER_CONTACT = 'widget-other@mautic-test.com';

    private User $withoutViewOther;

    private User $withViewOther;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutViewOther = $this->createUserWithPermission('widget-viewown', 'lead', 'leads', 2);
        $this->withViewOther    = $this->createUserWithPermission('widget-viewother', 'lead', 'leads', 6);

        $this->createContact(self::OWN_CONTACT, $this->withoutViewOther, 'Germany');
        $this->createContact(self::OTHER_CONTACT, $this->withViewOther, 'France');

        $this->em->flush();
    }

    public function testCreatedContactsWidgetHidesOtherPeoplesContactsWithoutViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withoutViewOther, 'created.leads');

        $this->assertStringContainsString(self::OWN_CONTACT, $html, 'The user should still see the contacts they own.');
        $this->assertStringNotContainsString(self::OTHER_CONTACT, $html, 'Without lead:leads:viewother the widget must not show another user\'s contacts.');
    }

    public function testCreatedContactsWidgetShowsEveryContactWithViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withViewOther, 'created.leads');

        $this->assertStringContainsString(self::OWN_CONTACT, $html);
        $this->assertStringContainsString(self::OTHER_CONTACT, $html);
    }

    public function testAnonymousVsIdentifiedWidgetCountsOnlyOwnContactsWithoutViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withoutViewOther, 'anonymous.vs.identified.leads');

        $this->assertSame(1, $this->sumChartData($html), 'Without lead:leads:viewother the chart must count only the user\'s own contacts.');
    }

    public function testAnonymousVsIdentifiedWidgetCountsEveryContactWithViewOther(): void
    {
        $html = $this->renderWidgetAsUser($this->withViewOther, 'anonymous.vs.identified.leads');

        $this->assertSame(2, $this->sumChartData($html));
    }

    public function testContactMapWidgetHidesOtherPeoplesContactsWithoutViewOther(): void
    {
        $countries = $this->getMapCountries($this->renderWidgetAsUser($this->withoutViewOther, 'map.of.leads'));

        $this->assertArrayHasKey('DE', $countries, 'The user\'s own contact should still be on the map.');
        $this->assertArrayNotHasKey('FR', $countries, 'Without lead:leads:viewother the map must not show another user\'s contacts.');
    }

    public function testContactMapWidgetShowsEveryContactWithViewOther(): void
    {
        $countries = $this->getMapCountries($this->renderWidgetAsUser($this->withViewOther, 'map.of.leads'));

        $this->assertArrayHasKey('DE', $countries);
        $this->assertArrayHasKey('FR', $countries);
    }

    /**
     * @return array<string, int|string> contact count keyed by country code
     */
    private function getMapCountries(string $widgetHtml): array
    {
        $this->assertSame(1, preg_match('#<div class="vector-map"[^>]*>(.*?)</div>#s', $widgetHtml, $matches));

        $countries = json_decode(html_entity_decode(trim($matches[1])), true);
        $this->assertIsArray($countries);

        return $countries;
    }

    private function createContact(string $email, User $owner, string $country): void
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setOwner($owner);
        $lead->setCountry($country);
        // getLeadList() only returns identified contacts.
        $lead->setDateIdentified(new \DateTime());
        $this->em->persist($lead);
    }
}
