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

/* FOSOAuthServerBundle:Authorize:authorize.html.twig */
class __TwigTemplate_fa074981c7d0cca75174ac14c03dd82c7d0e42f9472b5a37e6e30b6f95c08353 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'fos_oauth_server_content' => [$this, 'block_fos_oauth_server_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return 'FOSOAuthServerBundle::layout.html.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros       = $this->macros;
        $this->parent = $this->loadTemplate('FOSOAuthServerBundle::layout.html.twig', 'FOSOAuthServerBundle:Authorize:authorize.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_fos_oauth_server_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        $this->loadTemplate('FOSOAuthServerBundle:Authorize:authorize_content.html.twig', 'FOSOAuthServerBundle:Authorize:authorize.html.twig', 4)->display($context);
    }

    public function getTemplateName()
    {
        return 'FOSOAuthServerBundle:Authorize:authorize.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [50 => 4,  46 => 3,  35 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'FOSOAuthServerBundle:Authorize:authorize.html.twig', '/Users/mike.shaw/sites/mautic/vendor/friendsofsymfony/oauth-server-bundle/Resources/views/Authorize/authorize.html.twig');
    }
}
