<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\PasswordResetConfirmType;
use Mautic\UserBundle\Form\Type\PasswordResetType;
use Mautic\UserBundle\Model\UserModel;

class PublicController extends FormController
{
    /**
     * Generates a new password for the user and emails it to them.
     */
    public function passwordResetAction()
    {
        /** @var UserModel $model */
        $model = $this->getModel('user');

        $data   = ['identifier' => ''];
        $action = $this->generateUrl('mautic_user_passwordreset');
        $form   = $this->get('form.factory')->create(PasswordResetType::class, $data, ['action' => $action]);

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            if ($isValid = $this->isFormValid($form)) {
                //find the user
                $data = $form->getData();
                $user = $model->getRepository()->findByIdentifier($data['identifier']);

                try {
                    if (null !== $user) {
                        $model->sendResetEmail($user);
                    }
                    $this->addFlash('mautic.user.user.notice.passwordreset');
                } catch (\Exception $exception) {
                    $this->addFlash('mautic.user.user.notice.passwordreset.error', [], 'error');
                }

                return $this->redirectToRoute('login');
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => 'MauticUserBundle:Security:reset.html.twig',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }

    public function passwordResetConfirmAction(): mixed
    {
        /** @var UserModel $model */
        $model = $this->getModel('user');

        $data   = ['identifier' => '', 'password' => '', 'password_confirm' => ''];
        $action = $this->generateUrl('mautic_user_passwordresetconfirm');
        $form   = $this->get('form.factory')->create(PasswordResetConfirmType::class, [], ['action' => $action]);
        $token  = $this->request->query->get('token');

        if ($token) {
            $this->request->getSession()->set('resetToken', $token);
        }

        ///Check for a submitted form and process it
        if ('POST' == $this->request->getMethod()) {
            if ($isValid = $this->isFormValid($form)) {
                //find the user
                $data = $form->getData();
                /** @var User $user */
                $user = $model->getRepository()->findByIdentifier($data['identifier']);

                if (null == $user) {
                    $this->addFlash('mautic.user.user.notice.passwordreset.success');

                    return $this->redirectToRoute('login');
                } else {
                    if ($this->request->getSession()->has('resetToken')) {
                        $resetToken = $this->request->getSession()->get('resetToken');
                        $encoder    = $this->get('security.password_encoder');

                        if ($model->confirmResetToken($user, $resetToken)) {
                            $encodedPassword = $model->checkNewPassword($user, $encoder, $data['plainPassword']);
                            $user->setPassword($encodedPassword);
                            $model->saveEntity($user);
                            $this->addFlash('mautic.user.user.notice.passwordreset.success');
                            $this->request->getSession()->remove('resetToken');

                            return $this->redirectToRoute('login');
                        }

                        return $this->delegateView([
                            'viewParameters' => [
                                'form' => $form->createView(),
                            ],
                            'contentTemplate' => 'MauticUserBundle:Security:resetconfirm.html.twig',
                            'passthroughVars' => [
                                'route' => $action,
                            ],
                        ]);
                    } else {
                        $this->addFlash('mautic.user.user.notice.passwordreset.missingtoken');

                        return $this->redirectToRoute('mautic_user_passwordresetconfirm');
                    }
                }
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => 'MauticUserBundle:Security:resetconfirm.html.twig',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }
}
