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

/* TwigBundle:Exception:exception.atom.twig */
class __TwigTemplate_07daa68ab8a043d1a0125beff942b2f7135d6a10813080eb66a6e5f32517be10 extends \Twig\Template
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
        echo twig_include($this->env, $context, '@Twig/Exception/exception.xml.twig', ['exception' => ($context['exception'] ?? null)]);
        echo '
';
    }

    public function getTemplateName()
    {
        return 'TwigBundle:Exception:exception.atom.twig';
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
        return new Source('', 'TwigBundle:Exception:exception.atom.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/Exception/exception.atom.twig');
    }
}
