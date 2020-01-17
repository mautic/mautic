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

/* TwigBundle:Exception:exception.txt.twig */
class __TwigTemplate_7c1eb800cbada59e5be528b4d0e5cdfbbf1e17e9912c0c687b568ee2613c8a63 extends \Twig\Template
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
        echo '[exception] ';
        echo(((($context['status_code'] ?? null).' | ').($context['status_text'] ?? null)).' | ').twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'class', [], 'any', false, false, false, 1);
        echo '
[message] ';
        // line 2
        echo twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'message', [], 'any', false, false, false, 2);
        echo '
';
        // line 3
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'toarray', [], 'any', false, false, false, 3));
        foreach ($context['_seq'] as $context['i'] => $context['e']) {
            // line 4
            echo '[';
            echo $context['i'] + 1;
            echo '] ';
            echo twig_get_attribute($this->env, $this->source, $context['e'], 'class', [], 'any', false, false, false, 4);
            echo ': ';
            echo twig_get_attribute($this->env, $this->source, $context['e'], 'message', [], 'any', false, false, false, 4);
            echo '
';
            // line 5
            echo twig_include($this->env, $context, '@Twig/Exception/traces.txt.twig', ['exception' => $context['e']], false);
            echo '

';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['i'], $context['e'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:exception.txt.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [59 => 5,  50 => 4,  46 => 3,  42 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:exception.txt.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/exception.txt.twig');
    }
}
