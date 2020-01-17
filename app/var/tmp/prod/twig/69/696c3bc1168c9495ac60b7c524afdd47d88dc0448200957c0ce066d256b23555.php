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

/* BazingaOAuthServerBundle::authorize.html.twig */
class __TwigTemplate_b80e22953db7c874574a7a74405559a6dc5a442b6bb2b321f3bed2099bfaf8fc extends \Twig\Template
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
        echo '<div>
    <p>Do you want to authorize <strong>';
        // line 2
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context['consumer'] ?? null), 'name', [], 'any', false, false, false, 2), 'html', null, true);
        echo '</strong> access your informations ?</p>

    <form action="';
        // line 4
        echo $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getUrl('bazinga_oauth_server_authorize');
        echo '" method="POST">
        <input type="hidden" name="oauth_token" value="';
        // line 5
        echo twig_escape_filter($this->env, ($context['oauth_token'] ?? null), 'html', null, true);
        echo '" />
        <input type="hidden" name="oauth_callback" value="';
        // line 6
        echo twig_escape_filter($this->env, ($context['oauth_callback'] ?? null), 'html', null, true);
        echo '" />

        <input type="submit" name="submit_true" value="';
        // line 8
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans('authorize.accept', [], 'BazingaOAuthServerBundle'), 'html', null, true);
        echo '" />
        <input type="submit" name="submit_false" value="';
        // line 9
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans('authorize.reject', [], 'BazingaOAuthServerBundle'), 'html', null, true);
        echo '" />
    </form>
</div>
';
    }

    public function getTemplateName()
    {
        return 'BazingaOAuthServerBundle::authorize.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [62 => 9,  58 => 8,  53 => 6,  49 => 5,  45 => 4,  40 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'BazingaOAuthServerBundle::authorize.html.twig', '/Users/mike.shaw/sites/mautic/vendor/willdurand/oauth-server-bundle/Resources/views/authorize.html.twig');
    }
}
