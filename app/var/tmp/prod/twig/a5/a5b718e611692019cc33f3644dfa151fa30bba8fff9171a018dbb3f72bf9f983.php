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

/* TwigBundle:Exception:traces.txt.twig */
class __TwigTemplate_91720a2fa3a2a2029a404dbb0562dbdbaba015b828f1ec39d7d7ed56b8a6b9a5 extends \Twig\Template
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
        if (twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'trace', [], 'any', false, false, false, 1))) {
            // line 2
            echo twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'class', [], 'any', false, false, false, 2);
            echo ':
';
            // line 3
            if (!twig_test_empty(twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'message', [], 'any', false, false, false, 3))) {
                // line 4
                echo twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'message', [], 'any', false, false, false, 4);
                echo '
';
            }
            // line 6
            echo '
';
            // line 7
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context['exception'] ?? null), 'trace', [], 'any', false, false, false, 7));
            foreach ($context['_seq'] as $context['_key'] => $context['trace']) {
                // line 8
                echo '  ';
                echo twig_include($this->env, $context, '@Twig/Exception/trace.txt.twig', ['trace' => $context['trace']], false);
                echo '
';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['trace'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:traces.txt.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [57 => 8,  53 => 7,  50 => 6,  45 => 4,  43 => 3,  39 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:traces.txt.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/traces.txt.twig');
    }
}
