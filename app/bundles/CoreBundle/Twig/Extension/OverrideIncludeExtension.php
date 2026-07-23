<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\CoreExtension;

final readonly class OverrideIncludeExtension
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Override the built-in include() twig function with event dispatching.
     *
     * @param mixed[]         $context
     * @param string|string[] $template
     * @param mixed[]         $variables
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'include', needsEnvironment: true, needsContext: true, isSafe: ['html'])]
    public function includeWithEvent(Environment $env, array $context, $template, array $variables = [], bool $withContext = true, bool $ignoreMissing = false, bool $sandboxed = false): string
    {
        if ($withContext) {
            $variables = array_merge($context, $variables);
        }

        // Handle array of templates (try each one)
        if (is_array($template)) {
            $templates = [];
            foreach ($template as $templateName) {
                $event       = $this->dispatchCustomTemplateEvent((string) $templateName, $variables);
                $templates[] = $event->getTemplate();
            }

            // Use Twig's original include for array handling. Twig >= 3.28 returns
            // Twig\Markup here; cast to keep the string return type. Escaping is
            // unaffected as the function is registered with 'is_safe' => ['html'].
            return (string) CoreExtension::include($env, $context, $templates, $event->getVars(), $withContext, $ignoreMissing, $sandboxed);
        }

        // Handle single template
        $event = $this->dispatchCustomTemplateEvent((string) $template, $variables);

        // Use Twig's original include functionality. Twig >= 3.28 returns Twig\Markup;
        // cast to keep the string return type (escaping handled via 'is_safe' above).
        return (string) CoreExtension::include($env, $context, $event->getTemplate(), $event->getVars(), $withContext, $ignoreMissing, $sandboxed);
    }

    public function getName(): string
    {
        return 'mautic_override_include';
    }

    public function getPriority(): int
    {
        return 100; // High priority to ensure our extension overrides the core include function
    }

    /**
     * @param mixed[] $variables
     */
    private function dispatchCustomTemplateEvent(string $template, array $variables): CustomTemplateEvent
    {
        return $this->eventDispatcher->dispatch(
            new CustomTemplateEvent($this->requestStack->getCurrentRequest(), $template, $variables),
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE
        );
    }
}
