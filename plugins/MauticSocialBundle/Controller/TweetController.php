<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TweetController extends FormController
{
    protected function getModelName(): string
    {
        return 'social.tweet';
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return 'socialTweet';
    }

    protected function getRouteBase(): string
    {
        return 'mautic_tweet';
    }

    protected function getSessionBase($objectId = null): string
    {
        return 'mautic_tweet';
    }

    protected function getTemplateBase(): string
    {
        return '@MauticSocial/Tweet';
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.integration.Twitter';
    }

    protected function getPermissionBase(): string
    {
        return 'mauticSocial:tweets';
    }

    /**
     * Define options to pass to the form when it's being created.
     */
    protected function getEntityFormOptions(): array
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
            ? ($request->request->all()['twitter_tweet']['updateSelect'] ?? false)
            : $request->get('updateSelect', false);
    }

    /**
     * Set custom form themes, etc.
     *
     * @param string $action
     */
    protected function getFormView(FormInterface $form, $action): FormView
    {
        return $form->createView();
    }

    /**
     * @param int $page
     */
    public function indexAction(Request $request, $page = 1): Response
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
     */
    protected function getTemplateName($file): string
    {
        if (('form.html.twig' === $file) && 1 == $this->getCurrentRequest()->get('modal')) {
            return '@MauticSocial/Tweet/form_modal.html.twig';
        }

        return AbstractStandardFormController::getTemplateName($file);
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
