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

/* BazingaOAuthServerBundle::error.html.twig */
class __TwigTemplate_b4cccd8841660b0d681dda4e7596f16e0ae072bd5d3ee40257eb4acfbff237f1 extends \Twig\Template
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
        echo "<div>
    <h2>Ooops!</h2>
    <p>It appears you didn't allow <strong>";
        // line 3
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['consumer'] ?? null), 'name', [], 'any', false, false, false, 3), 'html', null, true);
        echo '</strong> to access your private information.</p>
</div>
';
    }

    public function getTemplateName()
    {
        return 'BazingaOAuthServerBundle::error.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [41 => 3,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'BazingaOAuthServerBundle::error.html.twig', '/Users/mike.shaw/sites/mautic/vendor/willdurand/oauth-server-bundle/Resources/views/error.html.twig');
    }
}
