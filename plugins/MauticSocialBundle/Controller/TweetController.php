<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

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
        return '@MauticSocial/Tweet';
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
        return 'mauticSocial:tweets';
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
        $request = $this->getCurrentRequest();

        return ('POST' === $request->getMethod())
            ? $request->request->get('twitter_tweet[updateSelect]', false)
            : $request->get('updateSelect', false);
    }

    /**
     * Set custom form themes, etc.
     *
     * @param string $action
     *
     * @return \Symfony\Component\Form\FormView
     */
    protected function getFormView(Form $form, $action)
    {
        return $form->createView();
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction(Request $request, $page = 1)
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * Get the template file.
     *
     * @return string
     */
    protected function getTemplateName($file)
    {
        if (('form.html.twig' === $file) && 1 == $this->getCurrentRequest()->get('modal')) {
            return '@MauticSocial/Tweet/form_modal.html.twig';
        }

        return '@MauticSocial/'.$file;
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * Displays details.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return $this->forward('MauticPlugin\MauticSocialBundle\Controller\TweetController::editAction', [
            'objectId' => $objectId,
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction(Request $request, $objectId)
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return parent::batchDeleteStandard($request);
    }
}
