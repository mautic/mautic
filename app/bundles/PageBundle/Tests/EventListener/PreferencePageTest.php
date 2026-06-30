<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Type\ContactFrequencyType;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\Translator;

final class PreferencePageTest extends MauticMysqlTestCase
{
    private EventDispatcherInterface $dispatcher;

    private FormFactoryInterface $formFactory;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences']        = true;
        $this->configParams['show_contact_frequency']          = true;
        $this->configParams['show_contact_pause_dates']        = true;
        $this->configParams['show_contact_preferred_channels'] = true;
        $this->configParams['show_contact_categories']         = true;
        $this->configParams['show_contact_segments']           = true;

        parent::setUp();

        $container         = self::getContainer();
        $this->dispatcher  = $container->get(EventDispatcherInterface::class);
        $this->formFactory = $container->get(FormFactoryInterface::class);
        $this->leadModel   = $container->get(LeadModel::class);
        $this->disableTranslations($container->get('translator.default'));
    }

    public function testDefaultLabelsWithoutForm(): void
    {
        $content = $this->dispatchEvent($this->createPage(), $this->createParams());

        $this->assertDefaultLabels($content);
    }

    public function testDefaultLabelsWithForm(): void
    {
        $category = $this->createCategory();
        $segment  = $this->createSegment();
        $this->em->flush();

        $form = $this->createForm()->createView();

        $params         = $this->createParams();
        $params['form'] = $form;
        $content        = $this->dispatchEvent($this->createPage(), $params);

        $this->assertDefaultLabels($content);

        Assert::assertStringContainsString($category->getTitle(), $content);
        Assert::assertStringContainsString($segment->getName(), $content);
    }

    public function testCustomLabelsWithoutForm(): void
    {
        $params                                    = $this->createParams();
        $params['categorylist']['label-text']      = 'My custom category label';
        $params['preferredchannel']['label-text']  = 'My custom preferred channel label';
        $params['saveprefsbutton']['btnText']      = 'My custom button text';
        $params['segmentlist']['label-text']       = 'My custom segment list text label';
        $params['channelfrequency']['label-text']  = 'My custom channel frequency text label';
        $params['channelfrequency']['label-text1'] = 'My custom channel frequency text 1 label';
        $params['channelfrequency']['label-text2'] = 'My custom channel frequency text 2 label';
        $params['channelfrequency']['label-text3'] = 'My custom channel frequency text 3 label';
        $params['channelfrequency']['label-text4'] = 'My custom channel frequency text 4 label';

        $content = $this->dispatchEvent($this->createPage(), $params);

        $this->assertCustomLabels($params, $content);

        Assert::assertStringContainsString($params['channelfrequency']['label-text'], $content);
        Assert::assertStringNotContainsString('mautic.lead.contact.me.label', $content);

        Assert::assertStringContainsString($params['channelfrequency']['label-text1'], $content);
        Assert::assertStringNotContainsString('mautic.lead.list.frequency.number', $content);

        Assert::assertStringContainsString($params['channelfrequency']['label-text2'], $content);
        Assert::assertStringNotContainsString('mautic.lead.list.frequency.times', $content);

        Assert::assertStringContainsString($params['channelfrequency']['label-text3'], $content);
        Assert::assertStringNotContainsString('mautic.lead.frequency.dates.label', $content);

        Assert::assertStringContainsString($params['channelfrequency']['label-text4'], $content);
        Assert::assertStringNotContainsString('mautic.lead.frequency.contact.end.date', $content);
    }

    public function testCustomLabelsWithForm(): void
    {
        $category = $this->createCategory();
        $segment  = $this->createSegment();
        $this->em->flush();

        $form = $this->createForm()->createView();

        $params                                    = $this->createParams();
        $params['form']                            = $form;
        $params['categorylist']['label-text']      = 'My custom category label';
        $params['preferredchannel']['label-text']  = 'My custom preferred channel label';
        $params['saveprefsbutton']['btnText']      = 'My custom button text';
        $params['segmentlist']['label-text']       = 'My custom segment list text label';

        $content = $this->dispatchEvent($this->createPage(), $params);

        $this->assertCustomLabels($params, $content);

        Assert::assertStringContainsString($category->getTitle(), $content);
        Assert::assertStringContainsString($segment->getName(), $content);
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setBundle('email');
        $category->setTitle('Category title');
        $category->setAlias($category->getTitle());
        $this->em->persist($category);

        return $category;
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment name');
        $segment->setAlias($segment->getName());
        $segment->setPublicName($segment->getName());
        $segment->setIsPreferenceCenter(true);
        $this->em->persist($segment);

        return $segment;
    }

    private function createPage(): Page
    {
        $page = new Page();
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml('
            <html lang="en">
                <body>
                    <p>This is the preference center page</p>
                    <div>{segmentlist}</div>
                    <div>{categorylist}</div>
                    <div>{preferredchannel}</div>
                    <div>{channelfrequency}</div>
                    <div>{saveprefsbutton}</div>
                </body>
            </html>
        ');

        return $page;
    }

    private function createForm(): FormInterface
    {
        return $this->formFactory->create(ContactFrequencyType::class, [
            'lead_channels' => [
                'subscribed_channels' => $this->leadModel->getContactChannels(new Lead()),
            ],
        ], [
            'channels'               => $this->leadModel->getPreferenceChannels(),
            'public_view'            => true,
            'preference_center_only' => true,
            'allow_extra_fields'     => true,
        ]);
    }

    /**
     * @return mixed[]
     */
    private function createParams(): array
    {
        return [
            'showContactFrequency'         => true,
            'showContactPauseDates'        => true,
            'showContactPreferredChannels' => true,
            'showContactCategories'        => true,
            'showContactSegments'          => true,
        ];
    }

    /**
     * @param mixed[] $params
     */
    private function dispatchEvent(Page $page, array $params): string
    {
        $event = new PageDisplayEvent($page->getCustomHtml(), $page, $params);
        $this->dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);

        return strip_tags($event->getContent());
    }

    private function assertDefaultLabels(string $content): void
    {
        Assert::assertStringContainsString('mautic.lead.form.categories', $content);
        Assert::assertStringContainsString('mautic.lead.contact.me.label', $content);
        Assert::assertStringContainsString('mautic.lead.list.frequency.number', $content);
        Assert::assertStringContainsString('mautic.lead.list.frequency.times', $content);
        Assert::assertStringContainsString('mautic.lead.frequency.dates.label', $content);
        Assert::assertStringContainsString('mautic.lead.frequency.contact.end.date', $content);
        Assert::assertStringContainsString('mautic.lead.list.frequency.preferred.channel', $content);
        Assert::assertStringContainsString('mautic.page.form.saveprefs', $content);
        Assert::assertStringContainsString('mautic.lead.form.list', $content);
        Assert::assertStringContainsString('mautic.lead.contact.me.label', $content);
        Assert::assertStringContainsString('mautic.lead.list.frequency.number', $content);
        Assert::assertStringContainsString('mautic.lead.list.frequency.times', $content);
        Assert::assertStringContainsString('mautic.lead.frequency.dates.label', $content);
        Assert::assertStringContainsString('mautic.lead.frequency.contact.end.date', $content);
    }

    /**
     * @param mixed[] $params
     */
    private function assertCustomLabels(array $params, string $content): void
    {
        Assert::assertStringContainsString($params['categorylist']['label-text'], $content);
        Assert::assertStringNotContainsString('mautic.lead.form.categories', $content);

        Assert::assertStringContainsString($params['preferredchannel']['label-text'], $content);
        Assert::assertStringNotContainsString('mautic.lead.list.frequency.preferred.channel', $content);

        Assert::assertStringContainsString($params['saveprefsbutton']['btnText'], $content);
        Assert::assertStringNotContainsString('mautic.page.form.saveprefs', $content);

        Assert::assertStringContainsString($params['segmentlist']['label-text'], $content);
        Assert::assertStringNotContainsString('mautic.lead.form.list', $content);
    }

    private function disableTranslations(Translator $translator): void
    {
        $translator->getCatalogue()->replace([]);
    }
}
