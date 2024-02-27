<?php

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Event as Events;
use MauticPlugin\MauticSocialBundle\Form\Type\MonitoringType;
use MauticPlugin\MauticSocialBundle\Form\Type\TwitterHashtagType;
use MauticPlugin\MauticSocialBundle\Form\Type\TwitterMentionType;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Monitoring>
 */
class MonitoringModel extends FormModel
{
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
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(MonitoringType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?Monitoring
    {
        return $id ? parent::getEntity($id) : new Monitoring();
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        switch ($action) {
            case 'pre_save':
                $name = SocialEvents::MONITOR_PRE_SAVE;
                break;
            case 'post_save':
                $name = SocialEvents::MONITOR_POST_SAVE;
                break;
            case 'pre_delete':
                $name = SocialEvents::MONITOR_PRE_DELETE;
                break;
            case 'post_delete':
                $name = SocialEvents::MONITOR_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\SocialEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param Monitoring $monitoringEntity
     * @param bool       $unlock
     */
    public function saveEntity($monitoringEntity, $unlock = true): void
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

    /**
     * @return \MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Monitoring::class);
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
     * @param string $type
     *
     * @return string|null
     */
    public function getFormByType($type)
    {
        return array_key_exists($type, $this->networkTypes) ? $this->networkTypes[$type]['form'] : null;
    }
}
