<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Form\Type\PasswordResetConfirmType;
use Mautic\UserBundle\Form\Type\PasswordResetType;
use Mautic\UserBundle\Form\Type\UserInviteRegistrationType;
use Mautic\UserBundle\Model\UserModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

final class PublicController extends AbstractStandardFormController
{
    private UserRepository $userRepository;

    private UserModel $userModel;

    #[Required]
    public function autowirePublicController(
        UserModel $userModel,
        UserRepository $userRepository,
    ): void {
        $this->userModel = $userModel;
        $this->userRepository = $userRepository;
    }

    protected function getModelName(): string
    {
        return '';
    }

    /**
     * Generates a new password for the user and emails it to them.
     */
    public function passwordResetAction(Request $request, LoggerInterface $logger): RedirectResponse|Response
    {
        $data   = ['identifier' => ''];
        $action = $this->generateUrl('mautic_user_passwordreset');
        $form   = $this->formFactory->create(PasswordResetType::class, $data, ['action' => $action]);

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            if ($isValid = $this->isFormValid($form)) {
                // find the user
                $data = $form->getData();
                $user = $this->userRepository->findByIdentifier($data['identifier']);

                /**
                 * Calculation of time to standardize fix response for vulnerability
                 * Users enumeration - forgot password. Constant response time is 1s.
                 */
                $desiredTime = 1.0;
                $startTime   = microtime(true);

                try {
                    if (null !== $user) {
                        $this->userModel->sendResetEmail($user);
                    }
                    $this->addFlashMessage('mautic.user.user.notice.passwordreset');
                } catch (\RuntimeException $e) {
                    $logger->error($this->translator->trans('mautic.user.password.reset.email.failed', [], 'messages').': '.$e->getMessage());
                    $this->addFlashMessage('mautic.user.user.notice.passwordreset.error', [], 'error');
                }

                $endTime       = microtime(true);
                $executionTime = $endTime - $startTime;

                if ($executionTime < $desiredTime) {
                    usleep((int) (($desiredTime - $executionTime) * 1000000));
                }

                return $this->redirectToRoute('login');
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticUser/Security/reset.html.twig',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }

    public function passwordResetConfirmAction(Request $request): RedirectResponse|Response
    {
        $action   = $this->generateUrl('mautic_user_passwordresetconfirm');
        $form     = $this->formFactory->create(PasswordResetConfirmType::class, [], ['action' => $action]);
        $token    = $request->query->get('token');
        $response = null;

        if ($token) {
            $request->getSession()->set('resetToken', $token);
        }

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            if ($isValid = $this->isFormValid($form)) {
                $data     = $form->getData();
                $response = $this->handlePasswordResetConfirm($request, $data);
            }
        }

        return $response ?? $this->renderPasswordResetConfirmForm($form, $action);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handlePasswordResetConfirm(Request $request, array $data): ?Response
    {
        $response = null;
        $user     = $this->userRepository->findByIdentifier($data['identifier']);

        if (null === $user) {
            $this->addFlashMessage('mautic.user.user.notice.passwordreset.success');

            $response = $this->redirectToRoute('login');
        } elseif (!$request->getSession()->has('resetToken')) {
            $this->addFlashMessage('mautic.user.user.notice.passwordreset.missingtoken');

            $response = $this->redirectToRoute('mautic_user_passwordresetconfirm');
        } elseif ($this->userModel->confirmResetToken($user, $request->getSession()->get('resetToken'))) {
            $encodedPassword = $this->userModel->checkNewPassword($user, $data['plainPassword']);
            $user->setPassword($encodedPassword);
            $this->userModel->saveEntity($user);

            $this->addFlashMessage('mautic.user.user.notice.passwordreset.success');
            $request->getSession()->remove('resetToken');

            $response = $this->redirectToRoute('login');
        }

        return $response;
    }

    private function renderPasswordResetConfirmForm(FormInterface $form, string $action): Response
    {
        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticUser/Security/resetconfirm.html.twig',
            'passthroughVars' => [
                'route' => $action,
            ],
        ]);
    }

    public function inviteAction(Request $request, UserModel $model, LoggerInterface $logger): RedirectResponse|Response
    {
        $token    = $request->attributes->getString('token');
        $invite   = $model->getInvite($token);
        $response = null;

        if (null === $invite) {
            $this->addFlashMessage('mautic.user.invite.invalid', [], 'error', 'flashes');

            $response = $this->redirectToRoute('login');
        } else {
            $action = $this->generateUrl('mautic_user_invite_register', ['token' => $token]);
            $user   = User::createFromInvite($invite);
            $form   = $this->formFactory->create(UserInviteRegistrationType::class, $user, [
                'action' => $action,
            ]);

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                // Check if user already exists before form validation
                if ($model->hasUserWithEmail((string) $invite->getEmail())) {
                    $this->addFlashMessage('mautic.user.invite.error.email_exists', [], 'error', 'flashes');
                    $response = $this->delegateView([
                        'viewParameters' => [
                            'form' => $form->createView(),
                        ],
                        'contentTemplate' => '@MauticUser/Security/register.html.twig',
                        'passthroughVars' => [
                            'route' => $action,
                        ],
                    ]);
                } elseif ($form->isSubmitted() && $form->isValid()) {
                    try {
                        $formUser          = $request->request->all()['user_invite_registration'] ?? [];
                        $submittedPassword = $formUser['plainPassword']['password'] ?? null;

                        $user->setPassword($model->checkNewPassword($user, $submittedPassword));
                        $model->markInviteUsed($invite);
                        $model->saveEntity($user);
                        $this->addFlashMessage('mautic.user.invite.account_created', [], 'notice', 'flashes');

                        $response = $this->redirectToRoute('login');
                    } catch (\Doctrine\DBAL\Exception $e) {
                        $logger->error($this->translator->trans('mautic.user.invite.registration.database.error', [], 'messages').': '.$e->getMessage());
                        $this->addFlashMessage('mautic.user.invite.error.database', [], 'error', 'flashes');
                    }
                }
            }

            $response ??= $this->delegateView([
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => '@MauticUser/Security/register.html.twig',
                'passthroughVars' => [
                    'route' => $action,
                ],
            ]);
        }

        return $response;
    }
}
