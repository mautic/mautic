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

/**
 * Class AbstractFormController.
 */
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
        $model  = $this->getModel($modelName);
        $entity = $model->getEntity($id);

        if ($this->canEdit($entity)) {
            if ($entity !== null && $entity->getCheckedOutBy() !== null) {
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
        $date      = $entity->getCheckedOut();
        $returnUrl = !empty($postActionVars['returnUrl'])
            ?
            urlencode($postActionVars['returnUrl'])
            :
            urlencode($this->generateUrl('mautic_dashboard_index'));
        $override = '';

        $modelClass           = $this->getModel($model);
        $nameFunction         = $modelClass->getNameGetter();
        $this->permissionBase = $modelClass->getPermissionBase();

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
                '%date%'     => $date->format($this->coreParametersHelper->getParameter('date_format_dateonly')),
                '%time%'     => $date->format($this->coreParametersHelper->getParameter('date_format_timeonly')),
                '%datetime%' => $date->format($this->coreParametersHelper->getParameter('date_format_full')),
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
     * @param Form $form
     *
     * @return int
     */
    protected function isFormCancelled(Form $form)
    {
        $name = $form->getName();

        return $this->request->request->get($name.'[buttons][cancel]', false, true) !== false;
    }

    /**
     * Checks to see if the form was applied or saved.
     *
     * @param $form
     *
     * @return bool
     */
    protected function isFormApplied($form)
    {
        $name = $form->getName();

        return $this->request->request->get($name.'[buttons][apply]', false, true) !== false;
    }

    /**
     * Binds form data, checks validity, and determines cancel request.
     *
     * @param Form $form
     *
     * @return int
     */
    protected function isFormValid(Form $form)
    {
        //bind request to the form
        $form->handleRequest($this->request);

        return $form->isValid();
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

        if ($this->getPermissionBase()) {
            if ($entity && $security->checkPermissionExists($this->getPermissionBase().':editown')) {
                return $security->hasEntityAccess(
                    $this->getPermissionBase().':editown',
                    $this->getPermissionBase().':editother',
                    $entity->getCreatedBy()
                );
            } elseif ($security->checkPermissionExists($this->getPermissionBase().':edit')) {
                return $security->isGranted(
                    $this->getPermissionBase().':edit'
                );
            }
        }

        return $this->get('mautic.helper.user')->getUser()->isAdmin();
    }

    /**
     * @param Form $copyFrom
     * @param Form $copyTo
     */
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
}
