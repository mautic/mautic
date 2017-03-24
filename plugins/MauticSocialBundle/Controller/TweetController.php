<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\Form;

/**
 * Class TweetController.
 */
class TweetController extends FormController
{
    public function __construct()
    {
        $this->setStandardParameters(
            'social.tweet', // model name
            'plugin:mauticSocial:tweets', // permission base
            'mautic_tweet', // route base
            'mautic_tweet', // session base
            'mautic.integration.Twitter', // lang string base
            'MauticSocialBundle:Tweet', // template base
            'mautic_webhook', // activeLink
            'socialTweet' // mauticContent
        );
    }

    /**
     * Define options to pass to the form when it's being created.
     *
     * @return array
     */
    protected function getEntityFormOptions()
    {
        return [
            'update_select'      => $this->getUpdateSelect(),
            'allow_extra_fields' => true,
        ];
    }

    /**
     * Get updateSelect value from request.
     *
     * @return string|bool
     */
    public function getUpdateSelect()
    {
        return ($this->request->getMethod() == 'POST')
            ? $this->request->request->get('twitter_tweet[updateSelect]', false, true)
            : $this->request->get('updateSelect', false);
    }

    /**
     * Set custom form themes, etc.
     *
     * @param Form   $form
     * @param string $action
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function getFormView(Form $form, $action)
    {
        return $this->setFormTheme($form, 'MauticSocialBundle:SubscribedEvents\Channel:message.html.php', 'MauticSocialBundle:FormTheme');
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction($page = 1)
    {
        return parent::indexStandard($page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function newAction()
    {
        return parent::newStandard();
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        return parent::editStandard($objectId, $ignorePost);
    }

    /**
     * Displays details on a Focus.
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return parent::viewStandard($objectId, 'webhook', 'webhook');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        return parent::cloneStandard($objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        return parent::deleteStandard($objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return parent::batchDeleteStandard();
    }
}
