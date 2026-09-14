<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Contracts\EventDispatcher\Event;

class CustomAssetsEvent extends Event
{
    public function __construct(
        protected AssetsHelper $assetsHelper,
    ) {
    }

    public function addCustomDeclaration(string $declaration, string $location = 'head', string $context = AssetsHelper::CONTEXT_APP): static
    {
        $this->assetsHelper->setContext($context)
            ->addCustomDeclaration($declaration, $location)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    public function addScript($script, string $location = 'head', bool $async = false, ?string $name = null, string $context = AssetsHelper::CONTEXT_APP): static
    {
        $this->assetsHelper->setContext($context)
            ->addScript($script, $location, $async, $name)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    public function addScriptDeclaration(string $script, string $location = 'head', string $context = AssetsHelper::CONTEXT_APP): static
    {
        $this->assetsHelper->setContext($context)
            ->addScriptDeclaration($script, $location)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    public function addStylesheet($stylesheet, string $context = AssetsHelper::CONTEXT_APP): static
    {
        $this->assetsHelper->setContext($context)
            ->addStylesheet($stylesheet)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    public function addStyleDeclaration($styles, string $context = AssetsHelper::CONTEXT_APP): static
    {
        $this->assetsHelper->setContext($context)
            ->addStyleDeclaration($styles)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }
}
