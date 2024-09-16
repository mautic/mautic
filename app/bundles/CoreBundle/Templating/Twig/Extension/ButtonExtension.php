<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ButtonExtension extends AbstractExtension
{
    protected ButtonHelper $buttonHelper;
    protected RequestStack $requestStack;

    public function __construct(ButtonHelper $buttonHelper, RequestStack $requestStack)
    {
        $this->buttonHelper = $buttonHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('buttonResetAndRender', [$this, 'resetAndRender'], ['is_safe' => ['all']]),
        ];
    }

    public function resetAndRender(string $location): string
    {
        return $this->buttonHelper->reset(
            $this->requestStack->getCurrentRequest(),
            $location
        )->renderButtons();
    }
}
