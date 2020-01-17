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

/* bootstrap_3_layout.html.twig */
class __TwigTemplate_fbee1685f95bed76ddbb37be831bfcf502ba5376149a5b2c1f71979dd6865073 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('bootstrap_base_layout.html.twig', 'bootstrap_3_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'bootstrap_base_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'form_widget_simple'   => [$this, 'block_form_widget_simple'],
                'button_widget'        => [$this, 'block_button_widget'],
                'money_widget'         => [$this, 'block_money_widget'],
                'checkbox_widget'      => [$this, 'block_checkbox_widget'],
                'radio_widget'         => [$this, 'block_radio_widget'],
                'form_label'           => [$this, 'block_form_label'],
                'choice_label'         => [$this, 'block_choice_label'],
                'checkbox_label'       => [$this, 'block_checkbox_label'],
                'radio_label'          => [$this, 'block_radio_label'],
                'checkbox_radio_label' => [$this, 'block_checkbox_radio_label'],
                'form_row'             => [$this, 'block_form_row'],
                'button_row'           => [$this, 'block_button_row'],
                'choice_row'           => [$this, 'block_choice_row'],
                'date_row'             => [$this, 'block_date_row'],
                'time_row'             => [$this, 'block_time_row'],
                'datetime_row'         => [$this, 'block_datetime_row'],
                'checkbox_row'         => [$this, 'block_checkbox_row'],
                'radio_row'            => [$this, 'block_radio_row'],
                'form_errors'          => [$this, 'block_form_errors'],
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
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 11
        echo '
';
        // line 12
        $this->displayBlock('button_widget', $context, $blocks);
        // line 16
        echo '
';
        // line 17
        $this->displayBlock('money_widget', $context, $blocks);
        // line 34
        echo '
';
        // line 35
        $this->displayBlock('checkbox_widget', $context, $blocks);
        // line 45
        echo '
';
        // line 46
        $this->displayBlock('radio_widget', $context, $blocks);
        // line 56
        echo '
';
        // line 58
        echo '
';
        // line 59
        $this->displayBlock('form_label', $context, $blocks);
        // line 63
        echo '
';
        // line 64
        $this->displayBlock('choice_label', $context, $blocks);
        // line 69
        echo '
';
        // line 70
        $this->displayBlock('checkbox_label', $context, $blocks);
        // line 75
        echo '
';
        // line 76
        $this->displayBlock('radio_label', $context, $blocks);
        // line 81
        echo '
';
        // line 82
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
        // line 106
        echo '
';
        // line 108
        echo '
';
        // line 109
        $this->displayBlock('form_row', $context, $blocks);
        // line 116
        echo '
';
        // line 117
        $this->displayBlock('button_row', $context, $blocks);
        // line 122
        echo '
';
        // line 123
        $this->displayBlock('choice_row', $context, $blocks);
        // line 127
        echo '
';
        // line 128
        $this->displayBlock('date_row', $context, $blocks);
        // line 132
        echo '
';
        // line 133
        $this->displayBlock('time_row', $context, $blocks);
        // line 137
        echo '
';
        // line 138
        $this->displayBlock('datetime_row', $context, $blocks);
        // line 142
        echo '
';
        // line 143
        $this->displayBlock('checkbox_row', $context, $blocks);
        // line 149
        echo '
';
        // line 150
        $this->displayBlock('radio_row', $context, $blocks);
        // line 156
        echo '
';
        // line 158
        echo '
';
        // line 159
        $this->displayBlock('form_errors', $context, $blocks);
    }

    // line 5
    public function block_form_widget_simple($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        if ((!(isset($context['type']) || array_key_exists('type', $context)) || !twig_in_filter(($context['type'] ?? null), [0 => 'file', 1 => 'hidden']))) {
            // line 7
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 7)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 7), '')) : ('')).' form-control'))]);
        }
        // line 9
        $this->displayParentBlock('form_widget_simple', $context, $blocks);
    }

    // line 12
    public function block_button_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 13
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 13)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 13), 'btn-default')) : ('btn-default')).' btn'))]);
        // line 14
        $this->displayParentBlock('button_widget', $context, $blocks);
    }

    // line 17
    public function block_money_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 18
        $context['prepend'] =  !(is_string($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context['money_pattern'] ?? null)) && is_string($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = '{{') && ('' === $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 || 0 === strpos($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4, $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144)));
        // line 19
        echo '    ';
        $context['append'] =  !(is_string($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = ($context['money_pattern'] ?? null)) && is_string($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = '}}') && ('' === $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 === substr($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b, -strlen($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002))));
        // line 20
        echo '    ';
        if ((($context['prepend'] ?? null) || ($context['append'] ?? null))) {
            // line 21
            echo '        <div class="input-group">
            ';
            // line 22
            if (($context['prepend'] ?? null)) {
                // line 23
                echo '                <span class="input-group-addon">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
            ';
            }
            // line 25
            $this->displayBlock('form_widget_simple', $context, $blocks);
            // line 26
            if (($context['append'] ?? null)) {
                // line 27
                echo '                <span class="input-group-addon">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
            ';
            }
            // line 29
            echo '        </div>
    ';
        } else {
            // line 31
            $this->displayBlock('form_widget_simple', $context, $blocks);
        }
    }

    // line 35
    public function block_checkbox_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 36
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 36)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 36), '')) : ('')))) : (((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 36)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 36), '')) : (''))));
        // line 37
        if (twig_in_filter('checkbox-inline', ($context['parent_label_class'] ?? null))) {
            // line 38
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
        } else {
            // line 40
            echo '<div class="checkbox">';
            // line 41
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
            // line 42
            echo '</div>';
        }
    }

    // line 46
    public function block_radio_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 47
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 47)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 47), '')) : ('')))) : (((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 47)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 47), '')) : (''))));
        // line 48
        if (twig_in_filter('radio-inline', ($context['parent_label_class'] ?? null))) {
            // line 49
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
        } else {
            // line 51
            echo '<div class="radio">';
            // line 52
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
            // line 53
            echo '</div>';
        }
    }

    // line 59
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 60
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 60)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 60), '')) : ('')).' control-label'))]);
        // line 61
        $this->displayParentBlock('form_label', $context, $blocks);
    }

    // line 64
    public function block_choice_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 66
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(twig_replace_filter(((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 66)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 66), '')) : ('')), ['checkbox-inline' => '', 'radio-inline' => '']))]);
        // line 67
        $this->displayBlock('form_label', $context, $blocks);
    }

    // line 70
    public function block_checkbox_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 71
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['for' => ($context['id'] ?? null)]);
        // line 73
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 76
    public function block_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 77
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['for' => ($context['id'] ?? null)]);
        // line 79
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 82
    public function block_checkbox_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 84
        if ((isset($context['widget']) || array_key_exists('widget', $context))) {
            // line 85
            if (($context['required'] ?? null)) {
                // line 86
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 86)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 86), '')) : ('')).' required'))]);
            }
            // line 88
            if ((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) {
                // line 89
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 89)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 89), '')) : ('')).' ').($context['parent_label_class'] ?? null)))]);
            }
            // line 91
            if ((!(($context['label'] ?? null) === false) && twig_test_empty(($context['label'] ?? null)))) {
                // line 92
                if (!twig_test_empty(($context['label_format'] ?? null))) {
                    // line 93
                    $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 94
($context['name'] ?? null), '%id%' =>                     // line 95
($context['id'] ?? null), ]);
                } else {
                    // line 98
                    $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
                }
            }
            // line 101
            echo '<label';
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['label_attr'] ?? null));
            foreach ($context['_seq'] as $context['attrname'] => $context['attrvalue']) {
                echo ' ';
                echo twig_escape_filter($this->env, $context['attrname'], 'html', null, true);
                echo '="';
                echo twig_escape_filter($this->env, $context['attrvalue'], 'html', null, true);
                echo '"';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['attrname'], $context['attrvalue'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            echo '>';
            // line 102
            echo $context['widget'] ?? null;
            echo ' ';
            ((!(($context['label'] ?? null) === false)) ? (print(twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['label'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true))) : (print('')));
            // line 103
            echo '</label>';
        }
    }

    // line 109
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 110
        echo '<div class="form-group';
        if (((!($context['compound'] ?? null) || (((isset($context['force_error']) || array_key_exists('force_error', $context))) ? (_twig_default_filter(($context['force_error'] ?? null), false)) : (false))) && !($context['valid'] ?? null))) {
            echo ' has-error';
        }
        echo '">';
        // line 111
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 112
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 113
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 114
        echo '</div>';
    }

    // line 117
    public function block_button_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 118
        echo '<div class="form-group">';
        // line 119
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 120
        echo '</div>';
    }

    // line 123
    public function block_choice_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 124
        $context['force_error'] = true;
        // line 125
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 128
    public function block_date_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 129
        $context['force_error'] = true;
        // line 130
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 133
    public function block_time_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 134
        $context['force_error'] = true;
        // line 135
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 138
    public function block_datetime_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 139
        $context['force_error'] = true;
        // line 140
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 143
    public function block_checkbox_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 144
        echo '<div class="form-group';
        if (!($context['valid'] ?? null)) {
            echo ' has-error';
        }
        echo '">';
        // line 145
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 146
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 147
        echo '</div>';
    }

    // line 150
    public function block_radio_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 151
        echo '<div class="form-group';
        if (!($context['valid'] ?? null)) {
            echo ' has-error';
        }
        echo '">';
        // line 152
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 153
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 154
        echo '</div>';
    }

    // line 159
    public function block_form_errors($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 160
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 161
            if (!Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
                echo '<span class="help-block">';
            } else {
                echo '<div class="alert alert-danger">';
            }
            // line 162
            echo '    <ul class="list-unstyled">';
            // line 163
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['errors'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['error']) {
                // line 164
                echo '<li><span class="glyphicon glyphicon-exclamation-sign"></span> ';
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['error'], 'message', [], 'any', false, false, false, 164), 'html', null, true);
                echo '</li>';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['error'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 166
            echo '</ul>
    ';
            // line 167
            if (!Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
                echo '</span>';
            } else {
                echo '</div>';
            }
        }
    }

    public function getTemplateName()
    {
        return 'bootstrap_3_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [512 => 167,  509 => 166,  501 => 164,  497 => 163,  495 => 162,  489 => 161,  487 => 160,  483 => 159,  479 => 154,  477 => 153,  475 => 152,  469 => 151,  465 => 150,  461 => 147,  459 => 146,  457 => 145,  451 => 144,  447 => 143,  443 => 140,  441 => 139,  437 => 138,  433 => 135,  431 => 134,  427 => 133,  423 => 130,  421 => 129,  417 => 128,  413 => 125,  411 => 124,  407 => 123,  403 => 120,  401 => 119,  399 => 118,  395 => 117,  391 => 114,  389 => 113,  387 => 112,  385 => 111,  379 => 110,  375 => 109,  370 => 103,  366 => 102,  351 => 101,  347 => 98,  344 => 95,  343 => 94,  342 => 93,  340 => 92,  338 => 91,  335 => 89,  333 => 88,  330 => 86,  328 => 85,  326 => 84,  322 => 82,  318 => 79,  316 => 77,  312 => 76,  308 => 73,  306 => 71,  302 => 70,  298 => 67,  296 => 66,  292 => 64,  288 => 61,  286 => 60,  282 => 59,  277 => 53,  275 => 52,  273 => 51,  270 => 49,  268 => 48,  266 => 47,  262 => 46,  257 => 42,  255 => 41,  253 => 40,  250 => 38,  248 => 37,  246 => 36,  242 => 35,  237 => 31,  233 => 29,  227 => 27,  225 => 26,  223 => 25,  217 => 23,  215 => 22,  212 => 21,  209 => 20,  206 => 19,  204 => 18,  200 => 17,  196 => 14,  194 => 13,  190 => 12,  186 => 9,  183 => 7,  181 => 6,  177 => 5,  173 => 159,  170 => 158,  167 => 156,  165 => 150,  162 => 149,  160 => 143,  157 => 142,  155 => 138,  152 => 137,  150 => 133,  147 => 132,  145 => 128,  142 => 127,  140 => 123,  137 => 122,  135 => 117,  132 => 116,  130 => 109,  127 => 108,  124 => 106,  122 => 82,  119 => 81,  117 => 76,  114 => 75,  112 => 70,  109 => 69,  107 => 64,  104 => 63,  102 => 59,  99 => 58,  96 => 56,  94 => 46,  91 => 45,  89 => 35,  86 => 34,  84 => 17,  81 => 16,  79 => 12,  76 => 11,  74 => 5,  71 => 4,  68 => 2,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'bootstrap_3_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/bootstrap_3_layout.html.twig');
    }
}
