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

/* LightSamlSpBundle::sessions.html.twig */
class __TwigTemplate_240bffdc1783421fa0ba995e47cd3e69905035201cf6e3ac3f29aec4cb788144 extends \Twig\Template
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
</head>
<body>

<h1>SAML Sessions</h1>
';
        // line 10
        $context['_parent']   = $context;
        $context['_seq']      = twig_ensure_traversable(($context['sessions'] ?? null));
        $context['_iterated'] = false;
        foreach ($context['_seq'] as $context['_key'] => $context['session']) {
            // line 11
            echo '    <ul data-session>
        <li data-idp="';
            // line 12
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'idpEntityId', [], 'any', false, false, false, 12), 'html', null, true);
            echo '">IDP: ';
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'idpEntityId', [], 'any', false, false, false, 12), 'html', null, true);
            echo '</li>
        <li data-sp="';
            // line 13
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'spEntityId', [], 'any', false, false, false, 13), 'html', null, true);
            echo '">SP: ';
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'spEntityId', [], 'any', false, false, false, 13), 'html', null, true);
            echo '</li>
        <li>NameID: ';
            // line 14
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'nameId', [], 'any', false, false, false, 14), 'html', null, true);
            echo '</li>
        <li>NameIDFormat: ';
            // line 15
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'nameIdFormat', [], 'any', false, false, false, 15), 'html', null, true);
            echo '</li>
        <li>SessionIndex: ';
            // line 16
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'sessionIndex', [], 'any', false, false, false, 16), 'html', null, true);
            echo '</li>
        <li>AuthnInstant: ';
            // line 17
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'sessionInstant', [], 'any', false, false, false, 17), 'Y-m-d H:i:s P'), 'html', null, true);
            echo '</li>
        <li>FirstAuthOn: ';
            // line 18
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'firstAuthOn', [], 'any', false, false, false, 18), 'Y-m-d H:i:s P'), 'html', null, true);
            echo '</li>
        <li>LastAuthOn: ';
            // line 19
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, twig_get_attribute($this->env, $this->source, $context['session'], 'lastAuthOn', [], 'any', false, false, false, 19), 'Y-m-d H:i:s P'), 'html', null, true);
            echo '</li>
    </ul>
';
            $context['_iterated'] = true;
        }
        if (!$context['_iterated']) {
            // line 22
            echo '    <p>There are no SAML sessions established</p>
';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['session'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 24
        echo '</body>
</html>
';
    }

    public function getTemplateName()
    {
        return 'LightSamlSpBundle::sessions.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [103 => 24,  96 => 22,  88 => 19,  84 => 18,  80 => 17,  76 => 16,  72 => 15,  68 => 14,  62 => 13,  56 => 12,  53 => 11,  48 => 10,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'LightSamlSpBundle::sessions.html.twig', '/Users/mike.shaw/sites/mautic/vendor/lightsaml/sp-bundle/src/LightSaml/SpBundle/Resources/views/sessions.html.twig');
    }
}
