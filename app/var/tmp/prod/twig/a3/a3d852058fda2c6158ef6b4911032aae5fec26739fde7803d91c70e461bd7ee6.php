<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Source;
use Twig\Template;

/* knp_menu_base.html.twig */
class __TwigTemplate_f07b5a26071d1efe34830ccb1ef9f294f6fb91747d58f2b6b48bd7c37837f2d6 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (twig_get_attribute($this->env, $this->source, ($context['options'] ?? null), 'compressed', [], 'any', false, false, false, 1)) {
            $this->displayBlock('compressed_root', $context, $blocks);
        } else {
            $this->displayBlock('root', $context, $blocks);
        }
    }

    public function getTemplateName()
    {
        return 'knp_menu_base.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'knp_menu_base.html.twig', '/Users/mike.shaw/sites/mautic/vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views/knp_menu_base.html.twig');
    }
}
