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

/* TwigBundle:Exception:exception.xml.twig */
class __TwigTemplate_950cc6412a59ff0fc8be0dc3690138cd785cb2400e33cca41759dc7d751cd136 extends \Twig\Template
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
        echo '<?xml version="1.0" encoding="';
        echo twig_escape_filter($this->env, $this->env->getCharset(), 'html', null, true);
        echo '" ?>

<error code="';
        // line 3
        echo twig_escape_filter($this->env, ($context['status_code'] ?? null), 'html', null, true);
        echo '" message="';
        echo twig_escape_filter($this->env, ($context['status_text'] ?? null), 'html', null, true);
        echo '">
';
        // line 4
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'toarray', [], 'any', false, false, false, 4));
        foreach ($context['_seq'] as $context['_key'] => $context['e']) {
            // line 5
            echo '    <exception class="';
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['e'], 'class', [], 'any', false, false, false, 5), 'html', null, true);
            echo '" message="';
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['e'], 'message', [], 'any', false, false, false, 5), 'html', null, true);
            echo '">
';
            // line 6
            echo twig_include($this->env, $context, '@Twig/Exception/traces.xml.twig', ['exception' => $context['e']], false);
            echo '
    </exception>
';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['e'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 9
        echo '</error>
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:exception.xml.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [69 => 9,  60 => 6,  53 => 5,  49 => 4,  43 => 3,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:exception.xml.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/exception.xml.twig');
    }
}
