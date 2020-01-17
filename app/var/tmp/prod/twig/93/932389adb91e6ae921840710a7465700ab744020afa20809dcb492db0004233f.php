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

/* knp_menu_ordered.html.twig */
class __TwigTemplate_3d3dcf0b67f853a168e767bf1f9526214c0b7385778263124e839c99199d996a extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'list' => [$this, 'block_list'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return 'knp_menu.html.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros       = $this->macros;
        $this->parent = $this->loadTemplate('knp_menu.html.twig', 'knp_menu_ordered.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_list($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        $macros['macros'] = $this->loadTemplate('knp_menu.html.twig', 'knp_menu_ordered.html.twig', 4)->unwrap();
        // line 5
        echo '
';
        // line 6
        if (((twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'hasChildren', [], 'any', false, false, false, 6) && !(0 === twig_get_attribute($this->env, $this->source, ($context['options'] ?? null), 'depth', [], 'any', false, false, false, 6))) && twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'displayChildren', [], 'any', false, false, false, 6))) {
            // line 7
            echo '    <ol';
            echo twig_call_macro($macros['macros'], 'macro_attributes', [($context['listAttributes'] ?? null)], 7, $context, $this->getSourceContext());
            echo '>
        ';
            // line 8
            $this->displayBlock('children', $context, $blocks);
            echo '
    </ol>
';
        }
    }

    public function getTemplateName()
    {
        return 'knp_menu_ordered.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [62 => 8,  57 => 7,  55 => 6,  52 => 5,  50 => 4,  46 => 3,  35 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'knp_menu_ordered.html.twig', '/Users/mike.shaw/sites/mautic/vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views/knp_menu_ordered.html.twig');
    }
}
