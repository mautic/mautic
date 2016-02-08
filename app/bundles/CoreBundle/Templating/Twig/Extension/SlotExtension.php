<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Factory\MauticFactory;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFunction;
use Mautic\CoreBundle\Templating\Helper\SlotsHelper;


class SlotExtension extends Twig_Extension
{
    /**
     * @var SlotsHelper
     */
    protected $helper;

    public function __construct(MauticFactory $factory)
    {
        $this->helper = $factory->getHelper('template.slots');
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            'slot' => new Twig_SimpleFunction('slot', [$this, 'getSlot'], ['is_safe' => ['html']]),
            'setSlot' => new Twig_SimpleFunction('setSlot', [$this, 'setSlot'], ['is_safe' => ['html']]),
            'slotHasContent' => new Twig_SimpleFunction('slotHasContent', [$this, 'slotHasContent'], ['is_safe' => ['html']])
        ];
    }

    public function getName()
    {
        return 'slot';
    }

    public function getSlot($name, $default = null)
    {
        ob_start();

        $this->helper->output($name, $default);

        return ob_get_clean();
    }

    public function setSlot($name, $content)
    {
        $this->helper->set($name, $content);
    }

    public function slotHasContent($name)
    {
        return $this->helper->hasContent($name);
    }
}