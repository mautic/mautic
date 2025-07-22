<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CampaignSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @dataProvider valueProvider
     */
    public function testComparingFormSubmissionValues(string $valueToCompare, string $submittedValue, bool $result, string $operator = '='): void
    {
        $formPayload = [
            'name'     => 'Test Form',
            'formType' => 'campaign',
            'fields'   => [
                [
                    'label'      => 'Select A',
                    'alias'      => 'select_a',
                    'type'       => 'select',
                    'properties' => [
                        'list' => [
                            'list' => [
                                [
                                    'label' => $valueToCompare,
                                    'value' => $valueToCompare,
                                ],
                                [
                                    'label' => $submittedValue,
                                    'value' => $submittedValue,
                                ],
                            ],
                        ],
                        'multiple' => false,
                    ],
                ], [
                    'label'     => 'Email',
                    'alias'     => 'email',
                    'type'      => 'email',
                    'leadField' => 'email',
                ], [
                    'label' => 'Submit',
                    'alias' => 'submit',
                    'type'  => 'button',
                ],
            ],
            'postAction'  => 'return',
        ];

        // Creating the form via API so it would create the submission table.
        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        $formId   = $response['form']['id'];

        // Submitting the form.
        $crawler    = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $saveButton = $crawler->selectButton('mauticform[submit]');
        $form       = $saveButton->form();
        $form['mauticform[select_a]']->setValue($submittedValue);
        $form['mauticform[email]']->setValue('testing@ampersand.select');

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        // Create some necessary entities.
        $campaignEvent = new Event();
        $campaignEvent->setName('Test Event');
        $campaignEvent->setType('form.field_value');
        $campaignEvent->setEventType('condition');
        $campaignEvent->setProperties(
            [
                'form'     => $formId,
                'field'    => 'select_a',
                'value'    => $valueToCompare,
                'operator' => $operator,
            ]
        );

        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $campaign->addEvent(1, $campaignEvent);

        $campaignEvent->setCampaign($campaign);

        $this->em->persist($campaignEvent);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->em->detach($campaignEvent);
        $this->em->detach($campaign);

        $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => 'testing@ampersand.select']);
        $event   = new CampaignExecutionEvent(
            [
                'lead'            => $contact,
                'event'           => $campaignEvent,
                'eventDetails'    => null,
                'systemTriggered' => false,
                'eventSettings'   => [],
            ],
            null
        );

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch($event, FormEvents::ON_CAMPAIGN_TRIGGER_CONDITION);

        Assert::assertSame('form', $event->getChannel());
        Assert::assertSame($result, $event->getResult());
    }

    public static function valueProvider(): \Generator
    {
        yield [
            'test & test',
            'test & test',
            true,
        ];

        yield [
            'test & test',
            'unicorn',
            false,
        ];

        yield [
            'test',
            'tester',
            false,
            '!like',
        ];

        yield [
            'test',
            'tst',
            true,
            '!like',
        ];
    }

    protected function beforeTearDown(): void
    {
        $tablePrefix = static::getContainer()->getParameter('mautic.db_table_prefix');

        if ($this->connection->createSchemaManager()->tablesExist("{$tablePrefix}form_results_1_test_form")) {
            $this->connection->executeStatement("DROP TABLE {$tablePrefix}form_results_1_test_form");
        }
    }
}
