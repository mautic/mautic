<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Form\FormError;

class PublicController extends FormController
{
    /**
     * Generates a new password for the user and emails it to them.
     */
    public function passwordResetAction()
    {
        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->getModel('user');

        $data   = ['identifier' => ''];
        $action = $this->generateUrl('mautic_user_passwordreset');
        $form   = $this->get('form.factory')->create('passwordreset', $data, ['action' => $action]);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if ($isValid = $this->isFormValid($form)) {
                //find the user
                $data = $form->getData();
                $user = $model->getRepository()->findByIdentifier($data['identifier']);

                if ($user == null) {
                    $form['identifier']->addError(new FormError($this->translator->trans('mautic.user.user.passwordreset.nouserfound', [], 'validators')));
                } else {
                    $model->sendResetEmail($user);

                    $this->addFlash('mautic.user.user.notice.passwordreset', [], 'notice', null, false);

                    return $this->redirect($this->generateUrl('login'));
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => 'MauticUserBundle:Security:reset.html.php',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }

    public function passwordResetConfirmAction()
    {
        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->getModel('user');

        $data   = ['identifier' => '', 'password' => '', 'password_confirm' => ''];
        $action = $this->generateUrl('mautic_user_passwordresetconfirm');
        $form   = $this->get('form.factory')->create('passwordresetconfirm', [], ['action' => $action]);
        $token  = $this->request->query->get('token');

        if ($token) {
            $this->request->getSession()->set('resetToken', $token);
        }

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if ($isValid = $this->isFormValid($form)) {
                //find the user
                $data = $form->getData();
                /** @var \Mautic\UserBundle\Entity\User $user */
                $user = $model->getRepository()->findByIdentifier($data['identifier']);

                if ($user == null) {
                    $form['identifier']->addError(new FormError($this->translator->trans('mautic.user.user.passwordreset.nouserfound', [], 'validators')));
                } else {
                    if ($this->request->getSession()->has('resetToken')) {
                        $resetToken = $this->request->getSession()->get('resetToken');
                        $encoder    = $this->get('security.encoder_factory')->getEncoder($user);

                        if ($model->confirmResetToken($user, $resetToken)) {
                            $encodedPassword = $model->checkNewPassword($user, $encoder, $data['plainPassword']);
                            $user->setPassword($encodedPassword);
                            $model->saveEntity($user);

                            $this->addFlash('mautic.user.user.notice.passwordreset.success', [], 'notice', null, false);

                            $this->request->getSession()->remove('resetToken');

                            return $this->redirect($this->generateUrl('login'));
                        }

                        return $this->delegateView([
                            'viewParameters' => [
                                'form' => $form->createView(),
                            ],
                            'contentTemplate' => 'MauticUserBundle:Security:resetconfirm.html.php',
                            'passthroughVars' => [
                                'route' => $action,
                            ],
                        ]);
                    } else {
                        $this->addFlash('mautic.user.user.notice.passwordreset.missingtoken', [], 'notice', null, false);

                        return $this->redirect($this->generateUrl('mautic_user_passwordresetconfirm'));
                    }
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => 'MauticUserBundle:Security:resetconfirm.html.php',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }
}
