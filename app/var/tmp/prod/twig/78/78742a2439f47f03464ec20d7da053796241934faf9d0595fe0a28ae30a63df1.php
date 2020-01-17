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

/* bootstrap_4_horizontal_layout.html.twig */
class __TwigTemplate_47a3406d4c8bbb7147645c30895842950963512d1020fe33a55f0d689c4db344 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('bootstrap_4_layout.html.twig', 'bootstrap_4_horizontal_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'bootstrap_4_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'form_label'        => [$this, 'block_form_label'],
                'form_label_class'  => [$this, 'block_form_label_class'],
                'form_row'          => [$this, 'block_form_row'],
                'fieldset_form_row' => [$this, 'block_fieldset_form_row'],
                'submit_row'        => [$this, 'block_submit_row'],
                'reset_row'         => [$this, 'block_reset_row'],
                'form_group_class'  => [$this, 'block_form_group_class'],
                'checkbox_row'      => [$this, 'block_checkbox_row'],
            ]
        );
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 2
        echo '
';
        // line 4
        echo '
';
        // line 5
        $this->displayBlock('form_label', $context, $blocks);
        // line 16
        echo '
';
        // line 17
        $this->displayBlock('form_label_class', $context, $blocks);
        // line 20
        echo '
';
        // line 22
        echo '
';
        // line 23
        $this->displayBlock('form_row', $context, $blocks);
        // line 35
        echo '
';
        // line 36
        $this->displayBlock('fieldset_form_row', $context, $blocks);
        // line 46
        echo '
';
        // line 47
        $this->displayBlock('submit_row', $context, $blocks);
        // line 55
        echo '
';
        // line 56
        $this->displayBlock('reset_row', $context, $blocks);
        // line 64
        echo '
';
        // line 65
        $this->displayBlock('form_group_class', $context, $blocks);
        // line 68
        echo '
';
        // line 69
        $this->displayBlock('checkbox_row', $context, $blocks);
    }

    // line 5
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        if ((($context['label'] ?? null) === false)) {
            // line 7
            echo '<div class="';
            $this->displayBlock('form_label_class', $context, $blocks);
            echo '"></div>';
        } else {
            // line 9
            if ((!(isset($context['expanded']) || array_key_exists('expanded', $context)) || !($context['expanded'] ?? null))) {
                // line 10
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 10)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 10), '')) : ('')).' col-form-label'))]);
            }
            // line 12
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 12)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 12), '')) : ('')).' ').$this->renderBlock('form_label_class', $context, $blocks)))]);
            // line 13
            $this->displayParentBlock('form_label', $context, $blocks);
        }
    }

    // line 17
    public function block_form_label_class($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 18
        echo 'col-sm-2';
    }

    // line 23
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 24
        if (((isset($context['expanded']) || array_key_exists('expanded', $context)) && ($context['expanded'] ?? null))) {
            // line 25
            $this->displayBlock('fieldset_form_row', $context, $blocks);
        } else {
            // line 27
            echo '<div class="form-group row';
            if (((!($context['compound'] ?? null) || (((isset($context['force_error']) || array_key_exists('force_error', $context))) ? (_twig_default_filter(($context['force_error'] ?? null), false)) : (false))) && !($context['valid'] ?? null))) {
                echo ' is-invalid';
            }
            echo '">';
            // line 28
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
            // line 29
            echo '<div class="';
            $this->displayBlock('form_group_class', $context, $blocks);
            echo '">';
            // line 30
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
            // line 31
            echo '</div>
    ';
            // line 32
            echo '</div>';
        }
    }

    // line 36
    public function block_fieldset_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 37
        echo '<fieldset class="form-group">
        <div class="row';
        // line 38
        if (((!($context['compound'] ?? null) || (((isset($context['force_error']) || array_key_exists('force_error', $context))) ? (_twig_default_filter(($context['force_error'] ?? null), false)) : (false))) && !($context['valid'] ?? null))) {
            echo ' is-invalid';
        }
        echo '">';
        // line 39
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 40
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 41
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 42
        echo '</div>
        </div>
';
        // line 44
        echo '</fieldset>';
    }

    // line 47
    public function block_submit_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 48
        echo '<div class="form-group row">';
        // line 49
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 50
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 51
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 52
        echo '</div>';
        // line 53
        echo '</div>';
    }

    // line 56
    public function block_reset_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 57
        echo '<div class="form-group row">';
        // line 58
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 59
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 60
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 61
        echo '</div>';
        // line 62
        echo '</div>';
    }

    // line 65
    public function block_form_group_class($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 66
        echo 'col-sm-10';
    }

    // line 69
    public function block_checkbox_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 70
        echo '<div class="form-group row">';
        // line 71
        echo '<div class="';
        $this->displayBlock('form_label_class', $context, $blocks);
        echo '"></div>';
        // line 72
        echo '<div class="';
        $this->displayBlock('form_group_class', $context, $blocks);
        echo '">';
        // line 73
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 74
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 75
        echo '</div>';
        // line 76
        echo '</div>';
    }

    public function getTemplateName()
    {
        return 'bootstrap_4_horizontal_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [267 => 76,  265 => 75,  263 => 74,  261 => 73,  257 => 72,  253 => 71,  251 => 70,  247 => 69,  243 => 66,  239 => 65,  235 => 62,  233 => 61,  231 => 60,  227 => 59,  223 => 58,  221 => 57,  217 => 56,  213 => 53,  211 => 52,  209 => 51,  205 => 50,  201 => 49,  199 => 48,  195 => 47,  191 => 44,  187 => 42,  185 => 41,  181 => 40,  179 => 39,  174 => 38,  171 => 37,  167 => 36,  162 => 32,  159 => 31,  157 => 30,  153 => 29,  151 => 28,  145 => 27,  142 => 25,  140 => 24,  136 => 23,  132 => 18,  128 => 17,  123 => 13,  121 => 12,  118 => 10,  116 => 9,  111 => 7,  109 => 6,  105 => 5,  101 => 69,  98 => 68,  96 => 65,  93 => 64,  91 => 56,  88 => 55,  86 => 47,  83 => 46,  81 => 36,  78 => 35,  76 => 23,  73 => 22,  70 => 20,  68 => 17,  65 => 16,  63 => 5,  60 => 4,  57 => 2,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'bootstrap_4_horizontal_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/bootstrap_4_horizontal_layout.html.twig');
    }
}
