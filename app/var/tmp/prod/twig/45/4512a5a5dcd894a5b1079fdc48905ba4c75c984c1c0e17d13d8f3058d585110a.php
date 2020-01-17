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

/* KnpMenuBundle::menu.html.twig */
class __TwigTemplate_d311a80938daddd29d999216bb9843dc281ea11646122e35056ac5eaee986234 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'label' => [$this, 'block_label'],
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
        $this->parent = $this->loadTemplate('knp_menu.html.twig', 'KnpMenuBundle::menu.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        $context['translation_domain'] = twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'extra', [0 => 'translation_domain', 1 => 'messages'], 'method', false, false, false, 4);
        // line 5
        $context['label'] = twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'label', [], 'any', false, false, false, 5);
        // line 6
        if (!(($context['translation_domain'] ?? null) === false)) {
            // line 7
            $context['label'] = $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'extra', [0 => 'translation_params', 1 => []], 'method', false, false, false, 7), ($context['translation_domain'] ?? null));
        }
        // line 9
        if ((twig_get_attribute($this->env, $this->source, ($context['options'] ?? null), 'allow_safe_labels', [], 'any', false, false, false, 9) && twig_get_attribute($this->env, $this->source, ($context['item'] ?? null), 'extra', [0 => 'safe_label', 1 => false], 'method', false, false, false, 9))) {
            echo $context['label'] ?? null;
        } else {
            echo twig_escape_filter($this->env, ($context['label'] ?? null), 'html', null, true);
        }
    }

    public function getTemplateName()
    {
        return 'KnpMenuBundle::menu.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [59 => 9,  56 => 7,  54 => 6,  52 => 5,  50 => 4,  46 => 3,  35 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'KnpMenuBundle::menu.html.twig', '/Users/mike.shaw/sites/mautic/vendor/knplabs/knp-menu-bundle/src/Resources/views/menu.html.twig');
    }
}
