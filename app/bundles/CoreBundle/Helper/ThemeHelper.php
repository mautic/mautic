<?php

namespace Mautic\CoreBundle\Helper;

class ThemeHelper {
    private $twig;
    private $sandboxEnv;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function renderThemeTemplate($template, array $context = []) {
        if (!isset($this->sandboxEnv)) {
            $mainTwig = $this->twig;
            $this->sandboxEnv = new Environment($this->twig->getLoader(), []);

            foreach ($this->twig->getExtensions() as $extension) {
                if (!$this->sandboxEnv->hasExtension($extension::class)) {
                    $this->sandboxEnv->addExtension($extension);
                }
            }

            $this->sandboxEnv->addExtension(new SandboxExtension(new ThemeSandboxPolicy(), true));

            $this->sandboxEnv->addRuntimeLoader(new class($mainTwig) implements RuntimeLoaderInterface {
                public function __construct(private Environment $twig) {}
                public function load(string $class): ?object {
                    try { return $this->twig->getRuntime($class); } catch (RuntimeError $e) { return null; }
                }
            });
        }

        return $this->sandboxEnv->render($template, $context);
    }
}