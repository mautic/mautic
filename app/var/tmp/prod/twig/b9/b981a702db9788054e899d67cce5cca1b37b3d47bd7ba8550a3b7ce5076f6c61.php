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

/* FOSOAuthServerBundle::layout.html.twig */
class __TwigTemplate_f96f548cb5676d1a95dd49995ff9634a23921c07d36977481f4e62cf78639c7a extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'fos_oauth_server_content' => [$this, 'block_fos_oauth_server_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
    </head>
    <body>
        <div>
            ';
        // line 8
        $this->displayBlock('fos_oauth_server_content', $context, $blocks);
        // line 10
        echo '        </div>
    </body>
</html>
';
    }

    // line 8
    public function block_fos_oauth_server_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 9
        echo '            ';
    }

    public function getTemplateName()
    {
        return 'FOSOAuthServerBundle::layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [60 => 9,  56 => 8,  49 => 10,  47 => 8,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'FOSOAuthServerBundle::layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/friendsofsymfony/oauth-server-bundle/Resources/views/layout.html.twig');
    }
}
