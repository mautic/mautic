<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\EventListener;

use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadFieldSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldModel $fieldModel,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::FIELD_PRE_DELETE    => ['onFieldPreDelete', 0],
        ];
    }

    /**
     * Check if Custom field is used in any segment before delete operation.
     */
    public function onFieldPreDelete(LeadFieldEvent $event): void
    {
        $field    = $event->getField();
        $segments = $this->fieldModel->getFieldSegments($field);

        if (count($segments)) {
            $url = $this->router->generate(
                'mautic_segment_index',
                ['search' => 'filters_field:'.$field->getAlias()]
            );
            $messageVars = [
                '%name%' => $field->getName(),
                '%id%'   => $field->getId(),
                '%url%'  => $url,
            ];
            $message = $this->translator->trans('mautic.core.notice.used.field', $messageVars, 'flashes');
            $event->addDependencyError($message);
        }
    }
}
