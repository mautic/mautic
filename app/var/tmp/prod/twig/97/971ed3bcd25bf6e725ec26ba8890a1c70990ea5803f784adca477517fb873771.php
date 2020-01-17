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

/* FOSOAuthServerBundle:Authorize:authorize_content.html.twig */
class __TwigTemplate_14e4745da617a5e13785e5c673c2d00be10f04151fc33852accf352f99ee5b98 extends \Twig\Template
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
        echo         $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderBlock(($context['form'] ?? null), 'form_start', ['method' => 'POST', 'action' => $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath('fos_oauth_server_authorize'), 'label_attr' => ['class' => 'fos_oauth_server_authorize']]);
        echo '
    <input type="submit" name="accepted" value="';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans('authorize.accept', [], 'FOSOAuthServerBundle'), 'html', null, true);
        echo '" />
    <input type="submit" name="rejected" value="';
        // line 3
        echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans('authorize.reject', [], 'FOSOAuthServerBundle'), 'html', null, true);
        echo '" />

    ';
        // line 5
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'client_id', [], 'any', false, false, false, 5), 'row');
        echo '
    ';
        // line 6
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'response_type', [], 'any', false, false, false, 6), 'row');
        echo '
    ';
        // line 7
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'redirect_uri', [], 'any', false, false, false, 7), 'row');
        echo '
    ';
        // line 8
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'state', [], 'any', false, false, false, 8), 'row');
        echo '
    ';
        // line 9
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'scope', [], 'any', false, false, false, 9), 'row');
        echo '
    ';
        // line 10
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'rest');
        echo '
</form>
';
    }

    public function getTemplateName()
    {
        return 'FOSOAuthServerBundle:Authorize:authorize_content.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [70 => 10,  66 => 9,  62 => 8,  58 => 7,  54 => 6,  50 => 5,  45 => 3,  41 => 2,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'FOSOAuthServerBundle:Authorize:authorize_content.html.twig', '/Users/mike.shaw/sites/mautic/vendor/friendsofsymfony/oauth-server-bundle/Resources/views/Authorize/authorize_content.html.twig');
    }
}
