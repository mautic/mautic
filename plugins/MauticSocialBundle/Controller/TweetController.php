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
    /**
     * @return mixed
     */
    protected function getModelName()
    {
        return 'social.tweet';
    }

    /**
     * @return mixed
     */
    protected function getJsLoadMethodPrefix()
    {
        return 'socialTweet';
    }

    /**
     * @return mixed
     */
    protected function getRouteBase()
    {
        return 'mautic_tweet';
    }

    /**
     * @param null $objectId
     *
     * @return mixed
     */
    protected function getSessionBase($objectId = null)
    {
        return 'mautic_tweet';
    }

    /**
     * @return mixed
     */
    protected function getTemplateBase()
    {
        return 'MauticSocialBundle:Tweet';
    }

    /**
     * @return mixed
     */
    protected function getControllerBase()
    {
        return 'MauticSocialBundle:Tweet';
    }

    /**
     * @return mixed
     */
    protected function getTranslationBase()
    {
        return 'mautic.integration.Twitter';
    }

    /**
     * @return mixed
     */
    protected function getPermissionBase()
    {
        return 'plugin:mauticSocial:tweets';
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
        return $this->setFormTheme($form, 'MauticSocialBundle:Tweet:form.html.php', ['MauticSocialBundle:FormTheme']);
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
     * Displays details.
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return parent::indexStandard(1);
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
