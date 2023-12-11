<?php

namespace Mautic\ChannelBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Factory\PageHelperFactoryInterface;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends AbstractStandardFormController
{
    use EntityContactsTrait;

    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        private RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return $this->batchDeleteStandard($request);
    }

    /**
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction(Request $request, $objectId)
    {
        return $this->cloneStandard($request, $objectId);
    }

    /**
     * @param bool $ignorePost
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return $this->editStandard($request, $objectId, $ignorePost);
    }

    /**
     * @param int $page
     */
    public function indexAction(Request $request, $page = 1): Response
    {
        return $this->indexStandard($request, $page);
    }

    public function newAction(Request $request): Response
    {
        return $this->newStandard($request);
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Request $request, $objectId)
    {
        return $this->viewStandard($request, $objectId, 'message', 'channel');
    }

    /**
     * @return mixed[]
     */
    protected function getViewArguments(array $args, $action): array
    {
        /** @var MessageModel $model */
        $model          = $this->getModel($this->getModelName());
        $viewParameters = [];
        switch ($action) {
            case 'index':
                $viewParameters = [
                    'headerTitle' => $this->translator->trans('mautic.channel.messages'),
                    'listHeaders' => [
                        [
                            'text'  => 'mautic.core.channels',
                            'class' => 'visible-md visible-lg',
                        ],
                    ],
                    'listItemTemplate'  => '@MauticChannel/Message/list_item.html.twig',
                    'enableCloneButton' => true,
                ];

                break;
            case 'view':
                $message = $args['viewParameters']['item'];

                // Init the date range filter form
                $returnUrl = $this->generateUrl(
                    'mautic_message_action',
                    [
                        'objectAction' => 'view',
                        'objectId'     => $message->getId(),
                    ]
                );

                [$dateFrom, $dateTo]     = $this->getViewDateRange($this->requestStack->getCurrentRequest(), $message->getId(), $returnUrl, 'local', $dateRangeForm);
                $chart                   = new LineChart(null, $dateFrom, $dateTo);

                /** @var Channel[] $channels */
                $channels        = $model->getChannels();
                $messageChannels = $message->getChannels();
                $chart->setDataset(
                    $this->translator->trans('mautic.core.all'),
                    $model->getLeadStatsPost($message->getId(), $dateFrom, $dateTo)
                );

                $messagedLeads = [
                    'all' => $this->forward(
                        'Mautic\ChannelBundle\Controller\MessageController::contactsAction',
                        [
                            'objectId'   => $message->getId(),
                            'page'       => $this->requestStack->getCurrentRequest()->getSession()->get('mautic.'.$this->getSessionBase('all').'.contact.page', 1),
                            'ignoreAjax' => true,
                            'channel'    => 'all',
                        ]
                    )->getContent(),
                ];

                foreach ($messageChannels as $channel) {
                    if ($channel->isEnabled() && isset($channels[$channel->getChannel()])) {
                        $chart->setDataset(
                            $channels[$channel->getChannel()]['label'],
                            $model->getLeadStatsPost($message->getId(), $dateFrom, $dateTo, $channel->getChannel())
                        );

                        $messagedLeads[$channel->getChannel()] = $this->forward(
                            'Mautic\ChannelBundle\Controller\MessageController::contactsAction',
                            [
                                'objectId' => $message->getId(),
                                'page'     => $this->requestStack->getCurrentRequest()->getSession()->get(
                                    'mautic.'.$this->getSessionBase($channel->getChannel()).'.contact.page',
                                    1
                                ),
                                'ignoreAjax' => true,
                                'channel'    => $channel->getChannel(),
                            ]
                        )->getContent();
                    }
                }

                $viewParameters = [
                    'channels'        => $channels,
                    'channelContents' => $model->getMessageChannels($message->getId()),
                    'dateRangeForm'   => $dateRangeForm->createView(),
                    'eventCounts'     => $chart->render(),
                    'messagedLeads'   => $messagedLeads,
                ];
                break;
            case 'new':
            case 'edit':
                $viewParameters = [
                    'channels' => $model->getChannels(),
                ];

                break;
        }

        $args['viewParameters'] = array_merge($args['viewParameters'], $viewParameters);

        return $args;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return $this->deleteStandard($request, $objectId);
    }

    protected function getTemplateBase(): string
    {
        return '@MauticChannel/Message';
    }

    protected function getFormView(FormInterface $form, $view): FormView
    {
        return $form->createView();
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return 'messages';
    }

    protected function getModelName(): string
    {
        return 'channel.message';
    }

    protected function getRouteBase(): string
    {
        return 'message';
    }

    /***

     *
     * @return string
     */
    protected function getSessionBase($objectId = null): string
    {
        return 'message'.(($objectId) ? '.'.$objectId : '');
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.channel.message';
    }

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function contactsAction(
        Request $request,
        PageHelperFactoryInterface $pageHelperFactory,
        $objectId,
        $channel,
        $page = 1
    ) {
        $filter = [];
        if ('all' !== $channel) {
            $returnUrl = $this->generateUrl(
                'mautic_message_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => $objectId,
                ]
            );
            [$dateFrom, $dateTo] = $this->getViewDateRange($request, $objectId, $returnUrl, 'UTC');

            $filter = [
                'channel' => $channel,
                [
                    'col'  => 'entity.date_triggered',
                    'expr' => 'between',
                    'val'  => [
                        $dateFrom->format('Y-m-d H:i:s'),
                        $dateTo->format('Y-m-d H:i:s'),
                    ],
                ],
            ];
        }

        return $this->generateContactsGrid(
            $request,
            $pageHelperFactory,
            $objectId,
            $page,
            'channel:messages:view',
            'message.'.$channel,
            'campaign_lead_event_log',
            $channel,
            null,
            $filter,
            [
                [
                    'type'       => 'join',
                    'from_alias' => 'entity',
                    'table'      => 'campaign_events',
                    'alias'      => 'event',
                    'condition'  => "entity.event_id = event.id and event.channel = 'channel.message' and event.channel_id = ".(int) $objectId,
                ],
            ],
            null,
            [
                'channel' => $channel ?: 'all',
            ],
            '.message-'.$channel
        );
    }
}
