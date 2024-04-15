<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractFormController extends CommonController
{
    protected ?string $permissionBase = null;

    /**
     * @return mixed
     */
    public function unlockAction(Request $request, $objectId, $objectModel)
    {
        $model                = $this->getModel($objectModel);
        $entity               = $model->getEntity($objectId);
        $this->permissionBase = $model->getPermissionBase();

        if ($this->canEdit($entity)) {
            if (null !== $entity && null !== $entity->getCheckedOutBy()) {
                $model->unlockEntity($entity);
            }
            $returnUrl = urldecode($request->get('returnUrl'));
            if (empty($returnUrl)) {
                $returnUrl = $this->generateUrl('mautic_dashboard_index');
            }

            $this->addFlashMessage(
                'mautic.core.action.entity.unlocked',
                [
                    '%name%' => urldecode($request->get('name')),
                ]
            );

            return $this->redirect($returnUrl);
        }

        return $this->accessDenied();
    }

    /**
     * Returns view to index with a locked out message.
     *
     * @param array  $postActionVars
     * @param object $entity
     * @param string $model
     * @param bool   $batch          Flag if a batch action is being performed
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|array
     */
    protected function isLocked($postActionVars, $entity, $model, $batch = false)
    {
        $date                   = $entity->getCheckedOut();
        $postActionVars         = $this->refererPostActionVars($postActionVars);
        $returnUrl              = $postActionVars['returnUrl'];
        $override               = '';

        $modelClass             = $this->getModel($model);
        $nameFunction           = $modelClass->getNameGetter();
        $this->permissionBase   = $modelClass->getPermissionBase();

        if ($this->canEdit($entity)) {
            $override = $this->translator->trans(
                'mautic.core.override.lock',
                [
                    '%url%' => $this->generateUrl(
                        'mautic_core_form_action',
                        [
                            'objectAction' => 'unlock',
                            'objectModel'  => $model,
                            'objectId'     => $entity->getId(),
                            'returnUrl'    => $returnUrl,
                            'name'         => urlencode($entity->$nameFunction()),
                        ]
                    ),
                ]
            );
        }

        $flash = [
            'type'    => 'error',
            'msg'     => 'mautic.core.error.locked',
            'msgVars' => [
                '%name%'       => $entity->$nameFunction(),
                '%user%'       => $entity->getCheckedOutByUser(),
                '%contactUrl%' => $this->generateUrl(
                    'mautic_user_action',
                    [
                        'objectAction' => 'contact',
                        'objectId'     => $entity->getCheckedOutBy(),
                        'entity'       => $model,
                        'id'           => $entity->getId(),
                        'subject'      => 'locked',
                        'returnUrl'    => $returnUrl,
                    ]
                ),
                '%date%'     => $date->format($this->coreParametersHelper->get('date_format_dateonly')),
                '%time%'     => $date->format($this->coreParametersHelper->get('date_format_timeonly')),
                '%datetime%' => $date->format($this->coreParametersHelper->get('date_format_full')),
                '%override%' => $override,
            ],
        ];

        if ($batch) {
            return $flash;
        }

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => [$flash],
                ]
            )
        );
    }

    /**
     * Checks to see if the form was cancelled.
     */
    protected function isFormCancelled(FormInterface $form): bool
    {
        $request = $this->getCurrentRequest();
        if (null === $request) {
            throw new \RuntimeException('Request is required.');
        }

        $formData = $request->request->get($form->getName());

        return is_array($formData) && array_key_exists('buttons', $formData) && array_key_exists('cancel', $formData['buttons']);
    }

    /**
     * Checks to see if the form was applied or saved.
     */
    protected function isFormApplied(FormInterface $form): bool
    {
        $request = $this->getCurrentRequest();
        if (null === $request) {
            throw new \RuntimeException('Request is required.');
        }

        $formData = $request->request->get($form->getName());

        return array_key_exists('buttons', $formData) && array_key_exists('apply', $formData['buttons']);
    }

    /**
     * Binds form data, checks validity, and determines cancel request.
     */
    protected function isFormValid(FormInterface $form): bool
    {
        $request = $this->getCurrentRequest();
        if (null === $request) {
            throw new \RuntimeException('Request is required.');
        }

        // bind request to the form
        $form->handleRequest($request);

        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Decide if current user can edit or can edit specific entity if entity is provided
     * For BC, if permissionBase property is not set, it allow to edit only to administrators.
     *
     * @param object $entity
     *
     * @return bool
     */
    protected function canEdit($entity = null)
    {
        if ($this->permissionBase) {
            $permissionBase = $this->permissionBase;
        } else {
            $permissionBase = $this->getPermissionBase();
        }

        if ($permissionBase) {
            if ($entity && $this->security->checkPermissionExists($permissionBase.':editown')) {
                return $this->security->hasEntityAccess(
                    $permissionBase.':editown',
                    $permissionBase.':editother',
                    $entity->getCreatedBy()
                );
            } elseif ($this->security->checkPermissionExists($permissionBase.':edit')) {
                return $this->security->isGranted(
                    $permissionBase.':edit'
                );
            }
        }

        return $this->user->isAdmin();
    }

    protected function copyErrorsRecursively(FormInterface $copyFrom, FormInterface $copyTo)
    {
        /** @var FormError $error */
        foreach ($copyFrom->getErrors() as $error) {
            $copyTo->addError($error);
        }

        foreach ($copyFrom->all() as $key => $child) {
            if ($child instanceof Form && $copyTo->has($key)) {
                $childTo = $copyTo->get($key);
                $this->copyErrorsRecursively($child, $childTo);
            }
        }
    }

    /**
     * generate $postActionVars with respect to available referer.
     *
     * @return array $postActionVars
     */
    protected function refererPostActionVars($vars)
    {
        $request = $this->getCurrentRequest();
        if (null === $request) {
            throw new \RuntimeException('Request is required.');
        }

        if (empty($request->server->get('HTTP_REFERER'))) {
            return $vars;
        }

        $returnUrl         = !empty($request->server->get('HTTP_REFERER')) ? $request->server->get('HTTP_REFERER') : '';
        $vars['returnUrl'] = $returnUrl;

        $urlMatcher  = explode('/s/', $returnUrl);
        $actionRoute = $this->get('router')->match('/s/'.$urlMatcher[1]);
        $objAction   = $actionRoute['objectAction'] ?? 'index';
        $routeCtrlr  = explode('\\', $actionRoute['_controller']);

        $defaultContentTemplate  = $routeCtrlr[0].$routeCtrlr[1].':'.ucfirst(str_replace('Bundle', '', $routeCtrlr[1])).':'.$objAction;
        $vars['contentTemplate'] ??= $defaultContentTemplate;

        $vars['passthroughVars']['activeLink'] = '#'.str_replace('_action', '_'.$objAction, $actionRoute['_route']);

        if (isset($actionRoute['objectId']) && $actionRoute['objectId'] > 0) {
            $vars['viewParameters']['objectId'] = $actionRoute['objectId'];
        }

        return $vars;
    }

    protected function getFormButton(FormInterface $form, array $elements): ClickableInterface
    {
        foreach ($elements as $element) {
            $form = $form->get($element);
        }

        \assert($form instanceof ClickableInterface);

        return $form;
    }
}
