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

/* TwigBundle::layout.html.twig */
class __TwigTemplate_e8449e8b30a4bc5b09572a517322ef238498f69da4d84a02f2f4ae596e090bea extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'title' => [$this, 'block_title'],
            'head'  => [$this, 'block_head'],
            'body'  => [$this, 'block_body'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="';
        // line 4
        echo twig_escape_filter($this->env, $this->env->getCharset(), 'html', null, true);
        echo '" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>';
        // line 7
        $this->displayBlock('title', $context, $blocks);
        echo '</title>
        <link rel="icon" type="image/png" href="';
        // line 8
        echo twig_include($this->env, $context, '@Twig/images/favicon.png.base64');
        echo '">
        <style>';
        // line 9
        echo twig_include($this->env, $context, '@Twig/exception.css.twig');
        echo '</style>
        ';
        // line 10
        $this->displayBlock('head', $context, $blocks);
        // line 11
        echo '    </head>
    <body>
        <header>
            <div class="container">
                <h1 class="logo">';
        // line 15
        echo twig_include($this->env, $context, '@Twig/images/symfony-logo.svg');
        echo ' Symfony Exception</h1>

                <div class="help-link">
                    <a href="https://symfony.com/doc">
                        <span class="icon">';
        // line 19
        echo twig_include($this->env, $context, '@Twig/images/icon-book.svg');
        echo '</span>
                        <span class="hidden-xs-down">Symfony</span> Docs
                    </a>
                </div>

                <div class="help-link">
                    <a href="https://symfony.com/support">
                        <span class="icon">';
        // line 26
        echo twig_include($this->env, $context, '@Twig/images/icon-support.svg');
        echo '</span>
                        <span class="hidden-xs-down">Symfony</span> Support
                    </a>
                </div>
            </div>
        </header>

        ';
        // line 33
        $this->displayBlock('body', $context, $blocks);
        // line 34
        echo '        ';
        echo twig_include($this->env, $context, '@Twig/base_js.html.twig');
        echo '
    </body>
</html>
';
    }

    // line 7
    public function block_title($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 10
    public function block_head($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 33
    public function block_body($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return 'TwigBundle::layout.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [121 => 33,  115 => 10,  109 => 7,  100 => 34,  98 => 33,  88 => 26,  78 => 19,  71 => 15,  65 => 11,  63 => 10,  59 => 9,  55 => 8,  51 => 7,  45 => 4,  40 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'TwigBundle::layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bundle/Resources/views/layout.html.twig');
    }
}
