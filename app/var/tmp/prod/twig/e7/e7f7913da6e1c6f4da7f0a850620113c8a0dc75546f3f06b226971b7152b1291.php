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

/* NoxlogicRateLimitBundle:Default:index.html.twig */
class __TwigTemplate_dcf721029caa042ce3ae4673d6588822c94d969639efbd131c0665f0c92676fb extends \Twig\Template
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
        echo 'Hello ';
        echo twig_escape_filter($this->env, ($context['name'] ?? null), 'html', null, true);
        echo '!
';
    }

    public function getTemplateName()
    {
        return 'NoxlogicRateLimitBundle:Default:index.html.twig';
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
        return new Source('', 'NoxlogicRateLimitBundle:Default:index.html.twig', '/Users/mike.shaw/sites/mautic/vendor/noxlogic/ratelimit-bundle/Noxlogic/RateLimitBundle/Resources/views/Default/index.html.twig');
    }
}
