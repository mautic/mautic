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

/* FOSOAuthServerBundle::form.html.twig */
class __TwigTemplate_427cfb213d5cf20debc884551bbf3affdd585de990e739d4b703c3644fb59aae extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'field_label' => [$this, 'block_field_label'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo '
';
        // line 2
        $this->displayBlock('field_label', $context, $blocks);
    }

    public function block_field_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        ob_start(function () { return ''; });
        // line 4
        echo '    <label for="';
        echo twig_escape_filter($this->env, ($context['id'] ?? null), 'html', null, true);
        echo '">';
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['id'] ?? null), [], 'FOSOAuthServerBundle'), 'html', null, true);
        echo '</label>
';
        echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));
    }

    public function getTemplateName()
    {
        return 'FOSOAuthServerBundle::form.html.twig';
    }

    public function getDebugInfo()
    {
        return [50 => 4,  48 => 3,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'FOSOAuthServerBundle::form.html.twig', '/Users/mike.shaw/sites/mautic/vendor/friendsofsymfony/oauth-server-bundle/Resources/views/form.html.twig');
    }
}
