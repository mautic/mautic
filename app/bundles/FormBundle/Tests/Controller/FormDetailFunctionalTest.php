<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class FormDetailFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * Test contact list exists for form.
     *
     * @dataProvider formContactProvider
     */
    public function testContactListExists(int $leadCount)
    {
        $container       = $this->getContainer();
        $formModel       = $container->get('mautic.form.model.form');
        $leadModel       = $container->get('mautic.lead.model.lead');
        $submissionModel = $container->get('mautic.form.model.submission');

        // Get per-page pagination limit from pageHelper to limit expected
        // visible number of contacts to actual number displayed at a time.
        $pageHelperFactory = $container->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFactory->make('mautic.form', 1);
        $pageLimit         = $pageHelper->getLimit();

        $form  = (new Form())->setName(uniqid());
        // $formModel->saveEntity($form);

        $field = new Field();
        $field->setForm($form);
        $field->setLabel('Email');
        $field->setAlias('email');
        $field->setType('text');
        $field->setLeadField('email');
        $form->addField('email', $field);

        $formModel->saveEntity($form);

        $request = new Request();

        // Symfony TemplatingHelper assumes a Session exists.
        $request->setSession(new Session());

        // StatSubscriber gets the request from the stack
        $container->get('request_stack')->push($request);

        for ($i = 0; $i < $leadCount; ++$i) {
            $lead = (new Lead())
                ->setFirstname('Test'.$i)
                ->setLastname('FormTest'.$i)
                ->setEmail('test'.$i.'@example.com');

            $leadModel->saveEntity($lead);

            $submissionModel->saveSubmission(['email' => $lead->getEmail()], [], $form, $request);
        }

        $crawler = $this->client->request('GET', sprintf('/s/forms/view/%d', $form->getId()));
        $cards   = $crawler->filter('#leads-container .contact-cards');

        $expected = min($leadCount, $pageLimit);

        Assert::assertSame($expected, $cards->count());
    }

    /**
     * Form contact provider.
     *
     * @return array
     */
    public function formContactProvider(): iterable
    {
        yield 'no leads'  => [0];
        yield '5 leads'   => [5];
        yield 'two pages' => [40];
    }
}
