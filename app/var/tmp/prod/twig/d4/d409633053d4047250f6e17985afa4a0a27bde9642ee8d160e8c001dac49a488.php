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

/* bootstrap_3_horizontal_layout.html.twig */
class __TwigTemplate_60c7adaee32a80b2f744a75da18d8cb457d4b0d38c85139061121be8c27e5d74 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('bootstrap_3_layout.html.twig', 'bootstrap_3_horizontal_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'bootstrap_3_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'form_start'       => [$this, 'block_form_start'],
                'form_label'       => [$this, 'block_form_label'],
                'form_label_class' => [$this, 'block_form_label_class'],
                'form_row'         => [$this, 'block_form_row'],
                'submit_row'       => [$this, 'block_submit_row'],
                'reset_row'        => [$this, 'block_reset_row'],
                'form_group_class' => [$this, 'block_form_group_class'],
                'checkbox_row'     => [$this, 'block_checkbox_row'],
            ]
        );
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 2
        echo '
';
        // line 3
        $this->displayBlock('form_start', $context, $blocks);
        // line 7
        echo '
';
        // line 9
        echo '
';
        // line 10
        $this->displayBlock('form_label', $context, $blocks);
        // line 18
        echo '
';
        // line 19
        $this->displayBlock('form_label_class', $context, $blocks);
        // line 22
        echo '
';
        // line 24
        echo '
';
        // line 25
        $this->displayBlock('form_row', $context, $blocks);
        // line 34
        echo '
';
        // line 35
        $this->displayBlock('submit_row', $context, $blocks);
        // line 43
        echo '
';
        // line 44
        $this->displayBlock('reset_row', $context, $blocks);
        // line 52
        echo '
';
        // line 53
        $this->displayBlock('form_group_class', $context, $blocks);
        // line 56
        echo '
';
        // line 57
        $this->displayBlock('checkbox_row', $context, $blocks);
    }

    // line 3
    public function block_form_start($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 4)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 4), '')) : ('')).' form-horizontal'))]);
        // line 5
        $this->displayParentBlock('form_start', $context, $blocks);
    }

    // line 10
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 11
        if ((($context['label'] ?? null) === false)) {
            // line 12
            echo '<div class="';
            $this->displayBlock('form_label_class', $context, $blocks);
            echo '"></div>';
        } else {
            // line 14
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 14)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 14), '')) : ('')).' ').$this->renderBlock('form_label_class', $context, $blocks)))]);
            // line 15
            $this->displayParentBlock('form_label', $context, $blocks);
        }
    }

    // line 19
    public function block_form_label_class($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 20
        echo 'col-sm-2';
    }

    // line 25
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 26
        echo '<div class="form-group';
        if (((!($context['compound'] ?? null) || (((isset($context['force_error']) || array_key_exists('force_error', $context))) ? (_twig_default_filter(($context['force_error'] ?? null), false)) : (false))) && !($context['valid'] ?? null))) {
            echo ' has-error';
        }
        echo '">';
        // line 27
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 28
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 29
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 30
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 31
        echo '</div>
';
        // line 32
        echo '</div>';
    }

    // line 35
    public function block_submit_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 36
        echo '<div class="form-group">';
        // line 37
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 38
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 39
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 40
        echo '</div>';
        // line 41
        echo '</div>';
    }

    // line 44
    public function block_reset_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 45
        echo '<div class="form-group">';
        // line 46
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 47
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 48
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 49
        echo '</div>';
        // line 50
        echo '</div>';
    }

    // line 53
    public function block_form_group_class($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 54
        echo 'col-sm-10';
    }

    // line 57
    public function block_checkbox_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 58
        echo '<div class="form-group';
        if (!($context['valid'] ?? null)) {
            echo ' has-error';
        }
        echo '">';
        // line 59
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 60
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 61
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 62
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 63
        echo '</div>';
        // line 64
        echo '</div>';
    }

    public function getTemplateName()
    {
        return 'bootstrap_3_horizontal_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [244 => 64,  242 => 63,  240 => 62,  238 => 61,  234 => 60,  230 => 59,  224 => 58,  220 => 57,  216 => 54,  212 => 53,  208 => 50,  206 => 49,  204 => 48,  200 => 47,  196 => 46,  194 => 45,  190 => 44,  186 => 41,  184 => 40,  182 => 39,  178 => 38,  174 => 37,  172 => 36,  168 => 35,  164 => 32,  161 => 31,  159 => 30,  157 => 29,  153 => 28,  151 => 27,  145 => 26,  141 => 25,  137 => 20,  133 => 19,  128 => 15,  126 => 14,  121 => 12,  119 => 11,  115 => 10,  111 => 5,  109 => 4,  105 => 3,  101 => 57,  98 => 56,  96 => 53,  93 => 52,  91 => 44,  88 => 43,  86 => 35,  83 => 34,  81 => 25,  78 => 24,  75 => 22,  73 => 19,  70 => 18,  68 => 10,  65 => 9,  62 => 7,  60 => 3,  57 => 2,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'bootstrap_3_horizontal_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/bootstrap_3_horizontal_layout.html.twig');
    }
}
