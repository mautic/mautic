<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Form\Type\ConfigType;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $preferences = [
            'show_contact_preferences'        => true,
            'show_contact_channels'           => true,
            'show_contact_frequency'          => true,
            'show_contact_pause_dates'        => true,
            'show_contact_preferred_channels' => true,
            'show_contact_categories'         => true,
            'show_contact_segments'           => true,
            'show_contact_dnc'                => true,
        ];
        $this->configParams = array_merge($this->configParams, $preferences);
        parent::setUp();
    }

    public function testUnsubscribeFormActionWithoutTheme(): void
    {
        $form = $this->getForm(null);

        $stat = $this->getStat($form);

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $content, $content);
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeFormActionWithThemeWithoutFormSupport(): void
    {
        $form = $this->getForm('aurora');

        $stat = $this->getStat($form);

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $content, $content);
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeFormActionWithThemeWithFormSupport(): void
    {
        $form = $this->getForm('blank');

        $stat = $this->getStat($form);

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $content, $content);
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testWithoutUnsubscribeFormAction(): void
    {
        $this->getForm('blank');

        $stat = $this->getStat();

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $content = $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('form/submit?formId=', $content, $content);
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeOptionsNotLandingPageAction(): void
    {
        $stat = $this->getStat();

        $segment = $this->createSegment('test-segment', []);
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);

        $this->createCategory('Newsletter', 'newslettter');

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        $content = $this->client->getResponse()->getContent();

        $this->assertTrue($this->client->getResponse()->isOk(), $content);
        $this->assertStringContainsString('I want to receive email', $content, $content);
        $this->assertStringContainsString('Do not contact more than', $content, $content);
        $this->assertStringContainsString('I prefer communication by', $content, $content);
        $this->assertStringContainsString('My segments', $content, $content);
        $this->assertStringContainsString('My categories', $content, $content);
    }

    public function testUnsubscribeOptionsDefaultLandingPageAction(): void
    {
        $this->useCleanupRollback = false;
        $this->setUpSymfony(array_merge($this->configParams, [ConfigType::DEFAULT_PREFERENCE_CENTER_PAGE => 1]));

        $this->createPrefsTestPage();

        $stat = $this->getStat();

        $segment = $this->createSegment('test-segment', []);
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);

        $this->createCategory('Newsletter', 'newslettter');

        $this->em->flush();
        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $content = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isOk(), $content);
        $this->assertStringContainsString('I want to receive email', $content, $content);
        $this->assertStringContainsString('Do not contact more than', $content, $content);
        $this->assertStringContainsString('I prefer custom communication by', $content, $content);
        $this->assertStringContainsString('My custom segments', $content, $content);
        $this->assertStringContainsString('My custom categories', $content, $content);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getStat(Form $form = null): Stat
    {
        $trackingHash = 'tracking_hash_unsubscribe_form_email';
        $emailName    = 'Test unsubscribe form email';

        $email = $this->getEmail($emailName, $form);

        $contact = new Lead();
        $contact->setEmail('xxx@xxx.com');
        $this->em->persist($contact);

        // Create a test email stat.
        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $stat->setLead($contact);
        $this->em->persist($stat);

        return $stat;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getForm(?string $formTemplate): Form
    {
        $formName = 'unsubscribe_test_form';

        $form = new Form();
        $form->setName($formName);
        $form->setAlias($formName);
        $form->setTemplate($formTemplate);
        $this->em->persist($form);

        return $form;
    }

    protected function getEmail(string $emailName, ?Form $form): Email
    {
        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $email->setUnsubscribeForm($form);
        $this->em->persist($email);

        return $email;
    }

    protected function createPrefsTestPage($pageParams=[]): Page
    {
        $page = new Page();

        $title       = $pageParams['title'] ?? 'Page:Page:LandingPageTracking';
        $alias       = $pageParams['alias'] ?? 'page-page-landingPageTracking';
        $isPublished = $pageParams['isPublished'] ?? true;
        $template    = $pageParams['template'] ?? 'blank';

        $page->setTitle($title);
        $page->setAlias($alias);
        $page->setIsPublished($isPublished);
        $page->setTemplate($template);
        $page->setCustomHtml($this->getPrefsCenterHtml());
        $page->setIsPreferenceCenter(true);

        $this->em->persist($page);
        $this->em->flush();

        return $page;
    }

    private function getPrefsCenterHtml(): string
    {
        return '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{pagetitle}
    </title>
    <meta name="description" content="{pagemetadescription}" />
  </head>
  <body class="ui-sortable" style="cursor: auto;">
    <div data-section-wrapper="1">
      <center>
        <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td>
                <div data-slot-container="1" style="min-height: 30px" class="ui-sortable">
                  <div data-slot="text" class="">Preferences center
                  </div>
                  <div data-slot="successmessage">
                    <span>Preferences saved.
                    </span>
                  </div>
                  <div data-slot="preferredchannel" data-param-label-text="I prefer custom communication by ">
                    <div class="preferred_channel text-left">
                      <div class="row">
                        <div class="form-group col-xs-12 ">
                          <label class="control-label">I prefer custom communication by 
                          </label>
                          <div class="choice-wrapper">
                            <select class="form-control">
                              <option value="email" selected="selected">Email
                              </option>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div data-slot="channelfrequency">
                    <div class="text-left">
                      <label class="label0">
                      </label>
                    </div>
                    <table class="table table-striped">
                      <tbody>
                        <tr>
                          <td>
                            <div class="text-left">
                              <input type="checkbox" checked="" />
                              <label class="control-label">
                                I want to receive %channel%
                              </label>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <div id="frequency_email" class="text-left row">
                              <div class="col-xs-6">
                                <label class="text-muted label1">Do not contact more than
                                </label>
                                <input type="text" class="frequency form-control" />
                                <label class="text-muted fw-n frequency-label label2">messages each
                                </label>
                                <select class="form-control">
                                  <option value="" selected="selected">
                                  </option>
                                </select>
                              </div>
                              <div class="col-xs-6">
                                <label class="text-muted label3">Pause from
                                </label>
                                <input type="date" class="form-control" />
                                <label class="frequency-label text-muted fw-n label4">to
                                </label>
                                <input type="date" class="form-control" />
                              </div>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div data-slot="segmentlist" data-param-label-text="My custom segments">
                    <div class="contact-segments">
                      <div class="text-left">
                        <label class="control-label">My custom segments
                        </label>
                      </div>
                      <div id="segment-1" class="text-left">
                        <input type="checkbox" id="lead_contact_frequency_rules_lead_lists_1" name="lead_contact_frequency_rules[lead_lists][]" autocomplete="false" value="2" checked="checked" />
                        <label for="lead_contact_frequency_rules_lead_lists_1">Contact Segment
                        </label>
                      </div>
                    </div>
                  </div>
                  <div data-slot="categorylist" data-param-label-text="My custom categories">
                    <div class="global-categories text-left">
                      <div>
                        <label class="control-label">My custom categories
                        </label>
                      </div>
                      <div id="category-1" class="text-left">
                        <input type="checkbox" id="lead_contact_frequency_rules_global_categories_1" name="lead_contact_frequency_rules[global_categories][]" autocomplete="false" value="1" checked="checked" />
                        <label for="lead_contact_frequency_rules_global_categories_1">Category
                        </label>
                      </div>
                    </div>
                  </div>
                  <div data-slot="saveprefsbutton">
                    <a class="button btn btn-default btn-save" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; display: inline-block;font-size: 16px; color: #ffffff;" background="">
                      Save preferences    
                    </a>
                    <div style="clear:both">
                    </div>
                  </div>
                  <div data-slot="donotcontact">
                    <a href="{dnc_url}">Unsubscribe
                    </a> from all marketing messages.                                
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </center>
    </div>
  </body>
</html>
';
    }
}
