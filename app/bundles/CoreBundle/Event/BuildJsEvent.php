<?php

namespace Mautic\CoreBundle\Event;

use MatthiasMullie\Minify;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildJsEvent extends Event
{
    /**
     * @param string         $js
     * @param BuildJsScope[] $acceptedScopes
     */
    public function __construct(
        private $js,
        private readonly bool $debugMode = false,
        private readonly array $acceptedScopes = [
            BuildJsScope::RUNTIME,
            BuildJsScope::ESSENTIAL,
            BuildJsScope::TRACKING,
        ],
    ) {
    }

    /**
     * @return string
     */
    public function getJs()
    {
        return $this->debugMode ? $this->js : new Minify\JS($this->js)->minify();
    }

    /**
     * @param string $js
     * @param string $section The section name. Shows when in debug mode
     */
    public function appendJs($js, $section = ''): static
    {
        return $this->appendJsForScope($js, BuildJsScope::TRACKING, $section);
    }

    /**
     * @param string $js
     * @param string $section The section name. Shows when in debug mode
     */
    public function appendJsForScope($js, BuildJsScope $scope, $section = ''): static
    {
        if (!$this->acceptsScope($scope)) {
            return $this;
        }

        if ($section && $this->debugMode) {
            $slashes = str_repeat('/', strlen($section) + 10);
            $this->js .= <<<JS
\n
{$slashes}
// {$section} Start
{$slashes}
\n
JS;
        }

        $this->js .= $js;

        if ($section && $this->debugMode) {
            $this->js .= <<<JS
\n
{$slashes}
// {$section} End
{$slashes}
JS;
        }

        return $this;
    }

    public function acceptsScope(BuildJsScope $scope): bool
    {
        return in_array($scope, $this->acceptedScopes, true);
    }
}
