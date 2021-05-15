<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

abstract class AbstractFormController extends CommonController
{
    use FormThemeTrait;

    protected $permissionBase;

    /**
     * @param $id
     * @param $modelName
     *
     * @return mixed
     */
    public function unlockAction($id, $modelName)
    {
        $model                = $this->getModel($modelName);
        $entity               = $model->getEntity($id);
        $this->permissionBase = $model->getPermissionBase();

        if ($this->canEdit($entity)) {
            if (null !== $entity && null !== $entity->getCheckedOutBy()) {
                $model->unlockEntity($entity);
            }
            $returnUrl = urldecode($this->request->get('returnUrl'));
            if (empty($returnUrl)) {
                $returnUrl = $this->generateUrl('mautic_dashboard_index');
            }

            $this->addFlash(
                'mautic.core.action.entity.unlocked',
                [
                    '%name%' => urldecode($this->request->get('name')),
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
            $override = $this->get('translator')->trans(
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
     *
     * @return bool
     */
    protected function isFormCancelled(Form $form)
    {
        $formData = $this->request->request->get($form->getName());

        return is_array($formData) && array_key_exists('buttons', $formData) && array_key_exists('cancel', $formData['buttons']);
    }

    /**
     * Checks to see if the form was applied or saved.
     *
     * @return bool
     */
    protected function isFormApplied(Form $form)
    {
        $formData = $this->request->request->get($form->getName());

        return array_key_exists('buttons', $formData) && array_key_exists('apply', $formData['buttons']);
    }

    /**
     * Binds form data, checks validity, and determines cancel request.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function isFormValid(Form $form, array $data = null)
    {
        //bind request to the form
        $form->handleRequest($this->request);

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
        $security = $this->get('mautic.security');

        if ($this->permissionBase) {
            $permissionBase = $this->permissionBase;
        } else {
            $permissionBase = $this->getPermissionBase();
        }

        if ($permissionBase) {
            if ($entity && $security->checkPermissionExists($permissionBase.':editown')) {
                return $security->hasEntityAccess(
                    $permissionBase.':editown',
                    $permissionBase.':editother',
                    $entity->getCreatedBy()
                );
            } elseif ($security->checkPermissionExists($permissionBase.':edit')) {
                return $security->isGranted(
                    $permissionBase.':edit'
                );
            }
        }

        return $this->get('mautic.helper.user')->getUser()->isAdmin();
    }

    protected function copyErrorsRecursively(Form $copyFrom, Form $copyTo)
    {
        /** @var $error FormError */
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
        if (empty($this->request->server->get('HTTP_REFERER'))) {
            return $vars;
        }

        $returnUrl         = !empty($this->request->server->get('HTTP_REFERER')) ? $this->request->server->get('HTTP_REFERER') : '';
        $vars['returnUrl'] = $returnUrl;

        $urlMatcher  = explode('/s/', $returnUrl);
        $actionRoute = $this->get('router')->match('/s/'.$urlMatcher[1]);
        $objAction   = isset($actionRoute['objectAction']) ? $actionRoute['objectAction'] : 'index';
        $routeCtrlr  = explode('\\', $actionRoute['_controller']);

        $defaultContentTemplate  = $routeCtrlr[0].$routeCtrlr[1].':'.ucfirst(str_replace('Bundle', '', $routeCtrlr[1])).':'.$objAction;
        $vars['contentTemplate'] = isset($vars['contentTemplate']) ? $vars['contentTemplate'] : $defaultContentTemplate;

        $vars['passthroughVars']['activeLink'] = '#'.str_replace('_action', '_'.$objAction, $actionRoute['_route']);

        if (isset($actionRoute['objectId']) && $actionRoute['objectId'] > 0) {
            $vars['viewParameters']['objectId'] = $actionRoute['objectId'];
        }

        return $vars;
    }
}
