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

/* TwigBundle:Exception:traces.xml.twig */
class __TwigTemplate_8f3cabb463fadfaf66f223a148bc524e3a509374a35d01cf4dcc28844ae10767 extends \Twig\Template
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
        echo '        <traces>
';
        // line 2
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'trace', [], 'any', false, false, false, 2));
        foreach ($context['_seq'] as $context['_key'] => $context['trace']) {
            // line 3
            echo '            <trace>
';
            // line 4
            echo twig_include($this->env, $context, '@Twig/Exception/trace.txt.twig', ['trace' => $context['trace']], false);
            echo '

            </trace>
';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['trace'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 8
        echo '        </traces>
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:traces.xml.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [57 => 8,  47 => 4,  44 => 3,  40 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:traces.xml.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/traces.xml.twig');
    }
}
