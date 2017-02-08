<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Controller;

use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\Form\Form;

/**
 * Class MessageController.
 */
class MessageController extends AbstractStandardFormController
{
    /**
     * @param $args
     * @param $view
     *
     * @return mixed
     */
    protected function customizeViewArguments($args, $view)
    {
        /** @var MessageModel $model */
        $model          = $this->getModel($this->modelName);
        $viewParameters = [];
        switch ($view) {
            case 'index':
                $viewParameters = [
                    'headerTitle' => $this->get('translator')->trans('mautic.channel.messages'),
                    'listHeaders' => [
                        [
                            'text'  => 'mautic.core.channels',
                            'class' => 'visible-md visible-lg',
                        ],
                    ],
                    'listItemTemplate'  => 'MauticChannelBundle:Message:list_item.html.php',
                    'enableCloneButton' => true,
                ];

                break;
            case 'view':
                // Init the date range filter form
                $returnUrl = $this->generateUrl(
                    'mautic_message_action',
                    [
                        'objectAction' => 'view',
                        'objectId'     => $args['viewParameters']['item']->getId(),
                    ]
                );

                $dateRangeValues = $this->request->get('daterange', []);
                $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $returnUrl]);
                $dateFrom        = new \DateTime($dateRangeForm['date_from']->getData());
                $dateTo          = new \DateTime($dateRangeForm['date_to']->getData());
                $logs            = $model->getMarketingMessagesEventLogs($args['viewParameters']['item']->getId(), $dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d'));
                $eventCounts     = $model->getLeadStatsPost($args['viewParameters']['item']->getId(), $dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d'));
                $viewParameters  = [
                    'logs'            => $logs,
                    'channels'        => $model->getChannels(),
                    'channelContents' => $model->getMessageChannels($args['viewParameters']['item']->getId()),
                    'dateRangeForm'   => $dateRangeForm->createView(),
                    'eventCounts'     => $eventCounts,
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
     * @param Form $form
     * @param      $view
     *
     * @return \Symfony\Component\Form\FormView
     */
    public function getStandardFormView(Form $form, $view)
    {
        $themes = ['MauticChannelBundle:FormTheme'];
        /** @var MessageModel $model */
        $model    = $this->getModel($this->modelName);
        $channels = $model->getChannels();
        foreach ($channels as $channel) {
            if (isset($channel['formTheme'])) {
                $themes[] = $channel['formTheme'];
            }
        }

        return $this->setFormTheme($form, 'MauticChannelBundle:Message:form.html.php', $themes);
    }

    /**
     * @param int $page
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction($page = 1)
    {
        return $this->indexStandard($page);
    }

    /**
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newAction()
    {
        return $this->newStandard();
    }

    /**
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editAction($objectId, $ignorePost = false)
    {
        return $this->editStandard($objectId, $ignorePost);
    }

    /**
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        return $this->viewStandard($objectId);
    }

    /**
     * @param $objectId
     *
     * @return \Mautic\CoreBundle\Controller\Response|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction($objectId)
    {
        return $this->cloneStandard($objectId);
    }

    /**
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteAction($objectId)
    {
        return $this->deleteStandard($objectId);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return $this->batchDeleteStandard();
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardTemplateBases()
    {
        $this->controllerBase = 'MauticChannelBundle:Message';
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardSessionBase()
    {
        $this->sessionBase = 'mautic.message';
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardRoutes()
    {
        $this->routeBase = 'message';
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardFrontendVariables()
    {
        $this->mauticContent = 'messages';
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardModelName()
    {
        $this->modelName = 'channel.message';
    }

    /**
     * {@inheritdoc}
     */
    protected function setStandardTranslationBase()
    {
        $this->translationBase = 'mautic.channel.message';
    }
}
