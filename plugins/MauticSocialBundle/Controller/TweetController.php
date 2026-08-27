<?php

namespace MauticPlugin\MauticSocialBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TweetController extends FormController
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
     */
    public function newAction(Request $request): Response
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
     * @param int $objectId
     */
    public function editAction(Request $request, $objectId, bool $ignorePost = false): Response
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * @param int $objectId
     */
    public function cloneAction(Request $request, $objectId): Response
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        return parent::batchDeleteStandard($request);
    }
}
