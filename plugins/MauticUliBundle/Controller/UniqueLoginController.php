<?php

namespace MauticPlugin\MauticUliBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use MauticPlugin\MauticUliBundle\Entity\UniqueLoginRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UniqueLoginController extends CommonController
{
    public function loginAction(
        Request $request,
        AuthorizationCheckerInterface $authChecker,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ): Response
    {
        // Check if user is already authenticated
        if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY') || $authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->addFlashMessage('mautic.user.user.notice.alreadyloggedin', [], FlashBag::LEVEL_WARNING);
            return $this->redirectToRoute('mautic_dashboard_index');
        }

        $hash = $request->query->get('hash');

        if (!$hash) {
            $this->addFlashMessage('mautic.uli.error.missing_hash', [], FlashBag::LEVEL_ERROR);
            return $this->renderAccessDenied();
        }

        try {
            /** @var UniqueLoginRepository $uliRepository */
            $uliRepository = $entityManager->getRepository(\MauticPlugin\MauticUliBundle\Entity\UniqueLogin::class);

            // Find valid unique login by hash
            $uniqueLogin = $uliRepository->findValidByHash($hash);

            if (!$uniqueLogin) {
                $logger->warning('ULI access denied: invalid or expired hash', [
                    'hash' => $hash,
                    'ip' => $request->getClientIp()
                ]);
                $this->addFlashMessage('mautic.uli.error.invalid_expired', [], FlashBag::LEVEL_ERROR);
                return $this->renderAccessDenied();
            }

            // Get user
            /** @var UserRepository $userRepository */
            $userRepository = $entityManager->getRepository(User::class);
            $user = $userRepository->find($uniqueLogin->getUserId());

            if (!$user) {
                $logger->error('ULI access denied: user not found', [
                    'user_id' => $uniqueLogin->getUserId(),
                    'hash' => $hash
                ]);
                $this->addFlashMessage('mautic.uli.error.user_not_found', [], FlashBag::LEVEL_ERROR);
                return $this->renderAccessDenied();
            }


            // Create authentication token
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

            // Set the token to the security context
            $this->container->get('security.token_storage')->setToken($token);

            // Fire the login event
            $event = new InteractiveLoginEvent($request, $token);
            $eventDispatcher->dispatch($event, 'security.interactive_login');

            // Delete the used hash
            $uliRepository->deleteByHash($hash);

            // Log successful login
            $logger->info('ULI successful login', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'ip' => $request->getClientIp(),
                'hash' => $hash
            ]);

            $this->addFlashMessage('mautic.uli.success.logged_in', ['%username%' => $user->getUsername()]);

            // Redirect to dashboard
            return $this->redirectToRoute('mautic_dashboard_index');

        } catch (\Exception $e) {
            $logger->error('ULI login error', [
                'hash' => $hash,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addFlashMessage('mautic.uli.error.system_error', [], FlashBag::LEVEL_ERROR);
            return $this->renderAccessDenied();
        }
    }

    private function renderAccessDenied(): Response
    {
        return $this->redirectToRoute('login');
    }
}