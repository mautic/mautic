<?php

namespace Mautic\CoreBundle\Twig\Helper;

/**
 * final class SlotsHelper.
 */
final class SlotsHelper
{
    /**
     * @var array<string, mixed>
     */
    private array $slots     = [];

    /**
     * @var array<string, mixed>
     */
    private array $openSlots = [];

    private bool $inBuilder = false;

    /**
     * Starts a new slot.
     *
     * This method starts an output buffer that will be
     * closed when the stop() method is called.
     *
     * @param string $name The slot name
     *
     * @throws \InvalidArgumentException if a slot with the same name is already started
     */
    public function start($name): void
    {
        if (\in_array($name, $this->openSlots)) {
            throw new \InvalidArgumentException(sprintf('A slot named "%s" is already started.', $name));
        }

        $this->openSlots[]  = $name;
        $this->slots[$name] = '';

        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Stops a slot.
     *
     * @throws \LogicException if no slot has been started
     */
    public function stop(): void
    {
        if (!$this->openSlots) {
            throw new \LogicException('No slot started.');
        }

        $name = array_pop($this->openSlots);

        $this->slots[$name] = ob_get_clean();
    }

    /**
     * Returns true if the slot exists.
     *
     * @param string $name The slot name
     */
    public function has($name): bool
    {
        return isset($this->slots[$name]);
    }

    /**
     * Gets the slot value.
     *
     * @param string      $name    The slot name
     * @param bool|string $default The default slot content
     *
     * @return string The slot content
     */
    public function get($name, $default = false)
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Sets a slot value.
     *
     * @param string $name    The slot name
     * @param string $content The slot content
     */
    public function set($name, $content): void
    {
        $this->slots[$name] = $content;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string
    {
        return 'slots';
    }

    /**
     * Appends a slot value if already set.
     */
    public function append(string $name, string $content): void
    {
        if (isset($this->slots[$name])) {
            if (is_array($this->slots[$name])) {
                $this->slots[$name][] = $content;
            } else {
                $this->slots[$name] .= ' '.$content;
            }
        } else {
            $this->slots[$name] = $content;
        }
    }

    /**
     * Checks if the slot has some content when a page is viewed in public.
     *
     * @param string|array<string, mixed> $names
     */
    public function hasContent($names): bool
    {
        // If we're in the builder, return true so all slots show.
        if ($this->inBuilder) {
            return true;
        }

        if (is_string($names)) {
            $names = [$names];
        }

        if (is_array($names)) {
            foreach ($names as $n) {
                // strip tags used to ensure we don't have empty tags.
                // Caused a bug with hasContent returning incorrectly. Whitelisted img to fix
                $hasContent = (bool) strip_tags(trim($this->slots[$n]), '<img><iframe>');
                if ($hasContent) {
                    return true;
                }
            }
        }

        return false;
    }

    public function inBuilder(bool $bool): void
    {
        $this->inBuilder = (bool) $bool;
    }

    /**
     * Outputs a slot.
     *
     * @return bool true if the slot is defined or if a default content has been provided, false otherwise
     */
    public function output(string $name, bool|string $default = false): bool
    {
        if (!isset($this->slots[$name])) {
            if (false !== $default) {
                echo $default;

                return true;
            }

            return false;
        }

        echo $this->slots[$name];

        return true;
    }
}
