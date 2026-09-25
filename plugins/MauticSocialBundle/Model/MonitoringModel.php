<?php

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository;
use MauticPlugin\MauticSocialBundle\Event as Events;
use MauticPlugin\MauticSocialBundle\Form\Type\MonitoringType;
use MauticPlugin\MauticSocialBundle\Form\Type\TwitterHashtagType;
use MauticPlugin\MauticSocialBundle\Form\Type\TwitterMentionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Monitoring>
 */
final class MonitoringModel extends FormModel
{
    public static function getName(): string
    {
        return 'social.monitoring';
    }

    private MonitoringRepository $monitoringRepository;

    #[Required]
    public function autowireMonitoringModel(
        MonitoringRepository $monitoringRepository,
    ): void {
        $this->monitoringRepository = $monitoringRepository;
    }

    /**
     * @var array<string, mixed>
     */
    private array $networkTypes = [
        'twitter_handle' => [
            'label' => 'mautic.social.monitoring.type.list.twitter.handle',
            'form'  => TwitterMentionType::class,
        ],
        'twitter_hashtag' => [
            'label' => 'mautic.social.monitoring.type.list.twitter.hashtag',
            'form'  => TwitterHashtagType::class,
        ],
    ];

    /**
     * @param object      $entity
     * @param string|null $action
     * @param mixed[]     $options
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(MonitoringType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Monitoring
    {
        return $id ? parent::getEntity($id) : new Monitoring();
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        $event = match ($action) {
            'pre_save'    => new Events\MonitorPreSaveEvent($entity, $isNew),
            'post_save'   => new Events\MonitorPostSaveEvent($entity, $isNew),
            'pre_delete'  => new Events\MonitorPreDeleteEvent($entity, $isNew),
            'post_delete' => new Events\MonitorPostDeleteEvent($entity, $isNew),
            default       => null,
        };

        if (null === $event || !$this->dispatcher->hasListeners($event::class)) {
            return null;
        }

        $this->dispatcher->dispatch($event);

        return $event;
    }

    /**
     * @param Monitoring $monitoringEntity
     */
    public function saveEntity(object $monitoringEntity, bool $unlock = true): void
    {
        // we're editing an existing record
        if (!$monitoringEntity->isNew()) {
            // increase the revision
            $revision = $monitoringEntity->getRevision();
            ++$revision;
            $monitoringEntity->setRevision($revision);
        } // is new
        else {
            $now = new \DateTime();
            $monitoringEntity->setDateAdded($now);
        }

        parent::saveEntity($monitoringEntity, $unlock);
    }

    public function getRepository(): MonitoringRepository
    {
        return $this->monitoringRepository;
    }

    public function getPermissionBase(): string
    {
        return 'mauticSocial:monitoring';
    }

    /**
     * @return string[]
     */
    public function getNetworkTypes(): array
    {
        $types = [];
        foreach ($this->networkTypes as $type => $data) {
            $types[$type] = $data['label'];
        }

        return $types;
    }

    /**
     * @return string|null
     */
    public function getFormByType(string $type)
    {
        return array_key_exists($type, $this->networkTypes) ? $this->networkTypes[$type]['form'] : null;
    }
}
