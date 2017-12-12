<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Controller;

use Mautic\CoreBundle\Exception as MauticException;
use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\Response;
use Mautic\CoreBundle\Helper\Chart\LineChart;

class MessengerController extends AbstractStandardFormController
{

    /**

     * {@inheritdoc}

     */

    protected function getJsLoadMethodPrefix()

    {

        return 'messenger';

    }



    /**

     * {@inheritdoc}

     */

    protected function getModelName()

    {
        return 'messengerMessage.messengerMessage';
    }



    /**

     * {@inheritdoc}

     */

    protected function getRouteBase()

    {

        return 'messenger';

    }



    /***

     * @param null $objectId

     *

     * @return string

     */

    protected function getSessionBase($objectId = null)

    {

        return 'messenger'.(($objectId) ? '.'.$objectId : '');

    }



    /**

     * {@inheritdoc}

     */

    protected function getTranslationBase()

    {

        return 'mautic.messengerMessage.message';

    }



    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return $this->batchDeleteStandard();
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
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        return $this->viewStandard($objectId, 'messengerMessage', 'messenger');
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
     * @param $args
     * @param $action
     *
     * @return mixed
     */
    protected function getViewArguments(array $args, $action)
    {
        /** @var MessageModel $model */
        $model          = $this->getModel($this->getModelName());
        $viewParameters = [];
        switch ($action) {
            case 'view':
                /** @var Import $entity */
                $entity = $args['entity'];

                /** @var \Mautic\LeadBundle\Model\ImportModel $model */
                $model = $this->getModel($this->getModelName());

                $args['viewParameters'] = array_merge(
                    $args['viewParameters'],
                    [
                    ]
                );

                break;
        }

        $args['viewParameters'] = array_merge($args['viewParameters'], $viewParameters);

        return $args;
    }


    /**
     * @return Response
     */
    public function callbackAction()
    {
        $verify_token = "mautic_bot_app";
        $hub_verify_token = null;
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $hub_verify_token = $_REQUEST['hub_verify_token'];
            if ($hub_verify_token === $verify_token) {
                return new Response($challenge);
            }
        }

    }

    public function checkboxAction()
    {
        $content = $this->get('mautic.plugin.helper.messenger')->getTemplateContent();
        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($content);
    }

    public function checkboxJsAction()
    {
        $content = $this->get('mautic.plugin.helper.messenger')->getTemplateContent(
            'MauticMessengerBundle:Plugin:checkbox_plugin_js.html.php'
        );
        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($content);

    }

}
