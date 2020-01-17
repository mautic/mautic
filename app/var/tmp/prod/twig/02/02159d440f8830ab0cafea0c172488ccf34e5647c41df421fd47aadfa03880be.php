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

/* @Twig/images/chevron-right.svg */
class __TwigTemplate_a216f958500f281098dd87465d2bd7ef76910e34f941aa7c6054b26442a995ff extends \Twig\Template
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
        echo '<svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path fill="#FFF" d="M1363 877l-742 742q-19 19-45 19t-45-19l-166-166q-19-19-19-45t19-45l531-531-531-531q-19-19-19-45t19-45L531 45q19-19 45-19t45 19l742 742q19 19 19 45t-19 45z"/></svg>
';
    }

    public function getTemplateName()
    {
        return '@Twig/images/chevron-right.svg';
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', '@Twig/images/chevron-right.svg', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/images/chevron-right.svg');
    }
}
