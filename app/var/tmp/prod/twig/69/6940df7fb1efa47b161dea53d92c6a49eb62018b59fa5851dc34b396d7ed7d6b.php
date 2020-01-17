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

/* @Twig/images/icon-minus-square.svg */
class __TwigTemplate_f4698c8cd0b8f8ea7ccfaedaffb97e6050f8dacca5aef7694b855de1314af3f2 extends \Twig\Template
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
        echo '<svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1408 960V832q0-26-19-45t-45-19H448q-26 0-45 19t-19 45v128q0 26 19 45t45 19h896q26 0 45-19t19-45zm256-544v960q0 119-84.5 203.5T1376 1664H416q-119 0-203.5-84.5T128 1376V416q0-119 84.5-203.5T416 128h960q119 0 203.5 84.5T1664 416z"/></svg>
';
    }

    public function getTemplateName()
    {
        return '@Twig/images/icon-minus-square.svg';
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', '@Twig/images/icon-minus-square.svg', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/images/icon-minus-square.svg');
    }
}
