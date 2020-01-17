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

/* TwigBundle:Exception:error.rdf.twig */
class __TwigTemplate_f3cbba9ad76d2b2b60e88e98a6c40138e53089f4acd34e10f2eb49a981e16ffa extends \Twig\Template
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
        echo twig_include($this->env, $context, '@Twig/Exception/error.xml.twig');
        echo '
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:error.rdf.twig';
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
        return new Source('', 'TwigBundle:Exception:error.rdf.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/error.rdf.twig');
    }
}
