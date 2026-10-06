<?php

namespace Mautic\ApiBundle\Model;

use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\ApiBundle\Entity\oAuth2\ClientRepository;
use Mautic\ApiBundle\Event\ClientEvent;
use Mautic\ApiBundle\Event\ClientPostDeleteEvent;
use Mautic\ApiBundle\Event\ClientPostSaveEvent;
use Mautic\ApiBundle\Form\Type\ClientType;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Client>
 */
final class ClientModel extends FormModel implements GlobalSearchInterface
{
    public static function getName(): string
    {
        return 'api.client';
    }

    public const string API_MODE_OAUTH2 = 'oauth2';

    private ?string $apiMode = null;

    private const string DEFAULT_API_MODE = 'oauth2';

    private RequestStack $requestStack;

    private ClientRepository $clientRepository;

    #[Required]
    public function autowireClientModel(
        RequestStack $requestStack,
        ClientRepository $clientRepository,
    ): void {
        $this->requestStack     = $requestStack;
        $this->clientRepository = $clientRepository;
    }

    private function getApiMode(): string
    {
        if (null !== $this->apiMode) {
            return $this->apiMode;
        }

        if (null !== $request = $this->requestStack->getCurrentRequest()) {
            return $request->attributes->all()['api_mode'] ?? $request->query->all()['api_mode'] ?? $request->request->all()['api_mode'] ?? $request->getSession()->get('mautic.client.filter.api_mode', self::DEFAULT_API_MODE);
        }

        return self::DEFAULT_API_MODE;
    }

    public function setApiMode(?string $apiMode): void
    {
        $this->apiMode = $apiMode;
    }

    public function getRepository(): ClientRepository
    {
        return $this->clientRepository;
    }

    public function getPermissionBase(): string
    {
        return 'api:clients';
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(['Client']);
        }

        $params = (!empty($action)) ? ['action' => $action] : [];

        return $this->formFactory->create(ClientType::class, $entity, $params);
    }

    public function getEntity($id = null): ?Client
    {
        if (null === $id) {
            return 'oauth2' === $this->getApiMode() ? new Client() : null;
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(['Client']);
        }

        $name = match ($action) {
            'post_save'   => ClientPostSaveEvent::class,
            'post_delete' => ClientPostDeleteEvent::class,
            default       => null,
        };

        if (null === $name) {
            return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (!$event instanceof ClientEvent) {
                $event = 'post_save' === $action
                    ? new ClientPostSaveEvent($entity, $isNew)
                    : new ClientPostDeleteEvent($entity, $isNew);
            }
            $this->dispatcher->dispatch($event);

            return $event;
        }

        return null;
    }

    public function getUserClients(User $user): array
    {
        return $this->clientRepository->getUserClients($user);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function revokeAccess(object $entity): void
    {
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(['Client']);
        }

        // remove the user from the client
        $entity->removeUser($this->userHelper->getUser());
        $this->saveEntity($entity);
    }
}
