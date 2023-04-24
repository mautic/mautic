<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\SlotsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SlotExtension extends AbstractExtension
{
    /**
     * @var SlotsHelper
     */
    protected $helper;

    public function __construct(SlotsHelper $slotsHelper)
    {
        $this->helper = $slotsHelper;
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('slot', [$this, 'getSlot'], ['is_safe' => ['html']]),
            new TwigFunction('slotHasContent', [$this, 'slotHasContent'], ['is_safe' => ['html']]),
        ];
    }

    public function getName(): string
    {
        return 'slot';
    }

    /**
     * @return string|false
     */
    public function getSlot(string $name, string $default = '')
    {
        ob_start();

        $this->helper->output($name, $default);

        return ob_get_clean();
    }

    /**
     * @param string|array<string, mixed> $name
     *
     * @return bool
     */
    public function slotHasContent($name)
    {
        return $this->helper->hasContent($name);
    }
}
