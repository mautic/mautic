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

/* TwigBundle:Exception:error.xml.twig */
class __TwigTemplate_cbb867eefabedcb0ed5f45fc590256bc939a16aaf30bc408b9ba7ac3d1f6d4ea extends \Twig\Template
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
        echo '" />
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:error.xml.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [43 => 3,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle:Exception:error.xml.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/error.xml.twig');
    }
}
