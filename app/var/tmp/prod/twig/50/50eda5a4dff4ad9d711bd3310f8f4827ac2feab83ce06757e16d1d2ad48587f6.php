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

/* TwigBundle:Exception:error.js.twig */
class __TwigTemplate_ce0419c85ef4b446fffc060a884167103b250eb5c9443f50c3cf2a2e86c4cd59 extends \Twig\Template
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
        echo '/*
';
        // line 2
        echo twig_escape_filter($this->env, ($context['status_code'] ?? null), 'js', null, true);
        echo ' ';
        echo twig_escape_filter($this->env, ($context['status_text'] ?? null), 'js', null, true);
        echo '

*/
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:error.js.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [40 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:error.js.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/error.js.twig');
    }
}
