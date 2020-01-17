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

/* LightSamlSpBundle::discovery.html.twig */
class __TwigTemplate_79546f5dbf646cd011294c5c23880d157a2a0eff682a0721b908b6b744b89ea7 extends \Twig\Template
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
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="';
        // line 6
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl('/media/css/libraries.css'), 'html', null, true);
        echo '" data-source="mautic">
    <link rel="stylesheet" href="';
        // line 7
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl('/media/css/app.css'), 'html', null, true);
        echo '" data-source="mautic">
</head>
<body>
<div class="container">
    <div class="well mt-15">
        <h4 class="text-center">SAML not configured or configured incorrectly.</h4>
    </div>
</div>
</body>
</html>
';
    }

    public function getTemplateName()
    {
        return 'LightSamlSpBundle::discovery.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [48 => 7,  44 => 6,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'LightSamlSpBundle::discovery.html.twig', '/Users/mike.shaw/sites/mautic/app/Resources/LightSamlSpBundle/views/discovery.html.twig');
    }
}
