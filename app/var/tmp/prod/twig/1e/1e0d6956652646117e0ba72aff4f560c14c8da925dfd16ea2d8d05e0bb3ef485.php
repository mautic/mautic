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

/* bootstrap_4_layout.html.twig */
class __TwigTemplate_5d430f67e1d902b5281f03b9f1bd5a9c2bf25d34a719aed924af1f950c031fd6 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('bootstrap_base_layout.html.twig', 'bootstrap_4_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'bootstrap_base_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'money_widget'           => [$this, 'block_money_widget'],
                'datetime_widget'        => [$this, 'block_datetime_widget'],
                'date_widget'            => [$this, 'block_date_widget'],
                'time_widget'            => [$this, 'block_time_widget'],
                'dateinterval_widget'    => [$this, 'block_dateinterval_widget'],
                'percent_widget'         => [$this, 'block_percent_widget'],
                'form_widget_simple'     => [$this, 'block_form_widget_simple'],
                'widget_attributes'      => [$this, 'block_widget_attributes'],
                'button_widget'          => [$this, 'block_button_widget'],
                'checkbox_widget'        => [$this, 'block_checkbox_widget'],
                'radio_widget'           => [$this, 'block_radio_widget'],
                'choice_widget_expanded' => [$this, 'block_choice_widget_expanded'],
                'form_label'             => [$this, 'block_form_label'],
                'form_label_errors'      => [$this, 'block_form_label_errors'],
                'checkbox_radio_label'   => [$this, 'block_checkbox_radio_label'],
                'form_row'               => [$this, 'block_form_row'],
                'form_errors'            => [$this, 'block_form_errors'],
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
        $this->displayBlock('money_widget', $context, $blocks);
        // line 26
        echo '
';
        // line 27
        $this->displayBlock('datetime_widget', $context, $blocks);
        // line 34
        echo '
';
        // line 35
        $this->displayBlock('date_widget', $context, $blocks);
        // line 42
        echo '
';
        // line 43
        $this->displayBlock('time_widget', $context, $blocks);
        // line 50
        echo '
';
        // line 51
        $this->displayBlock('dateinterval_widget', $context, $blocks);
        // line 107
        echo '
';
        // line 108
        $this->displayBlock('percent_widget', $context, $blocks);
        // line 116
        echo '
';
        // line 117
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 128
        $this->displayBlock('widget_attributes', $context, $blocks);
        // line 135
        $this->displayBlock('button_widget', $context, $blocks);
        // line 139
        echo '
';
        // line 140
        $this->displayBlock('checkbox_widget', $context, $blocks);
        // line 154
        echo '
';
        // line 155
        $this->displayBlock('radio_widget', $context, $blocks);
        // line 169
        echo '
';
        // line 170
        $this->displayBlock('choice_widget_expanded', $context, $blocks);
        // line 181
        echo '
';
        // line 183
        echo '
';
        // line 184
        $this->displayBlock('form_label', $context, $blocks);
        // line 214
        echo '
';
        // line 215
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
        // line 252
        echo '
';
        // line 254
        echo '
';
        // line 255
        $this->displayBlock('form_row', $context, $blocks);
        // line 264
        echo '
';
        // line 266
        echo '
';
        // line 267
        $this->displayBlock('form_errors', $context, $blocks);
    }

    // line 5
    public function block_money_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        $context['prepend'] =  !(is_string($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context['money_pattern'] ?? null)) && is_string($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = '{{') && ('' === $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 || 0 === strpos($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4, $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144)));
        // line 7
        $context['append'] =  !(is_string($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = ($context['money_pattern'] ?? null)) && is_string($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = '}}') && ('' === $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 === substr($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b, -strlen($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002))));
        // line 8
        if ((($context['prepend'] ?? null) || ($context['append'] ?? null))) {
            // line 9
            echo '<div class="input-group';
            echo twig_escape_filter($this->env, (((isset($context['group_class']) || array_key_exists('group_class', $context))) ? (_twig_default_filter(($context['group_class'] ?? null), '')) : ('')), 'html', null, true);
            echo '">';
            // line 10
            if (($context['prepend'] ?? null)) {
                // line 11
                echo '<div class="input-group-prepend">
                    <span class="input-group-text">';
                // line 12
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
                </div>';
            }
            // line 15
            $this->displayBlock('form_widget_simple', $context, $blocks);
            // line 16
            if (($context['append'] ?? null)) {
                // line 17
                echo '<div class="input-group-append">
                    <span class="input-group-text">';
                // line 18
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
                </div>';
            }
            // line 21
            echo '</div>';
        } else {
            // line 23
            $this->displayBlock('form_widget_simple', $context, $blocks);
        }
    }

    // line 27
    public function block_datetime_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 28
        if (((($context['widget'] ?? null) != 'single_text') && !($context['valid'] ?? null))) {
            // line 29
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 29)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 29), '')) : ('')).' form-control is-invalid'))]);
            // line 30
            $context['valid'] = true;
        }
        // line 32
        $this->displayParentBlock('datetime_widget', $context, $blocks);
    }

    // line 35
    public function block_date_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 36
        if (((($context['widget'] ?? null) != 'single_text') && !($context['valid'] ?? null))) {
            // line 37
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 37)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 37), '')) : ('')).' form-control is-invalid'))]);
            // line 38
            $context['valid'] = true;
        }
        // line 40
        $this->displayParentBlock('date_widget', $context, $blocks);
    }

    // line 43
    public function block_time_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 44
        if (((($context['widget'] ?? null) != 'single_text') && !($context['valid'] ?? null))) {
            // line 45
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 45)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 45), '')) : ('')).' form-control is-invalid'))]);
            // line 46
            $context['valid'] = true;
        }
        // line 48
        $this->displayParentBlock('time_widget', $context, $blocks);
    }

    // line 51
    public function block_dateinterval_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 52
        if (((($context['widget'] ?? null) != 'single_text') && !($context['valid'] ?? null))) {
            // line 53
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 53)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 53), '')) : ('')).' form-control is-invalid'))]);
            // line 54
            $context['valid'] = true;
        }
        // line 56
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 57
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 59
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 59)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 59), '')) : ('')).' form-inline'))]);
            // line 60
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 61
            if (($context['with_years'] ?? null)) {
                // line 62
                echo '<div class="col-auto">
                ';
                // line 63
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 63), 'label');
                echo '
                ';
                // line 64
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 64), 'widget');
                echo '
            </div>';
            }
            // line 67
            if (($context['with_months'] ?? null)) {
                // line 68
                echo '<div class="col-auto">
                ';
                // line 69
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 69), 'label');
                echo '
                ';
                // line 70
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 70), 'widget');
                echo '
            </div>';
            }
            // line 73
            if (($context['with_weeks'] ?? null)) {
                // line 74
                echo '<div class="col-auto">
                ';
                // line 75
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 75), 'label');
                echo '
                ';
                // line 76
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 76), 'widget');
                echo '
            </div>';
            }
            // line 79
            if (($context['with_days'] ?? null)) {
                // line 80
                echo '<div class="col-auto">
                ';
                // line 81
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 81), 'label');
                echo '
                ';
                // line 82
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 82), 'widget');
                echo '
            </div>';
            }
            // line 85
            if (($context['with_hours'] ?? null)) {
                // line 86
                echo '<div class="col-auto">
                ';
                // line 87
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 87), 'label');
                echo '
                ';
                // line 88
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 88), 'widget');
                echo '
            </div>';
            }
            // line 91
            if (($context['with_minutes'] ?? null)) {
                // line 92
                echo '<div class="col-auto">
                ';
                // line 93
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 93), 'label');
                echo '
                ';
                // line 94
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 94), 'widget');
                echo '
            </div>';
            }
            // line 97
            if (($context['with_seconds'] ?? null)) {
                // line 98
                echo '<div class="col-auto">
                ';
                // line 99
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 99), 'label');
                echo '
                ';
                // line 100
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 100), 'widget');
                echo '
            </div>';
            }
            // line 103
            if (($context['with_invert'] ?? null)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'invert', [], 'any', false, false, false, 103), 'widget');
            }
            // line 104
            echo '</div>';
        }
    }

    // line 108
    public function block_percent_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 109
        echo '<div class="input-group">';
        // line 110
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 111
        echo '<div class="input-group-append">
            <span class="input-group-text">%</span>
        </div>
    </div>';
    }

    // line 117
    public function block_form_widget_simple($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 118
        if ((!(isset($context['type']) || array_key_exists('type', $context)) || (($context['type'] ?? null) != 'hidden'))) {
            // line 119
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter(((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 119)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 119), '')) : ('')).' form-control').((('file' == (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), '')) : ('')))) ? ('-file') : (''))))]);
        }
        // line 121
        if (((isset($context['type']) || array_key_exists('type', $context)) && ((($context['type'] ?? null) == 'range') || (($context['type'] ?? null) == 'color')))) {
            // line 122
            echo '        ';
            // line 123
            $context['required'] = false;
        }
        // line 125
        $this->displayParentBlock('form_widget_simple', $context, $blocks);
    }

    // line 128
    public function block_widget_attributes($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 129
        if (!($context['valid'] ?? null)) {
            // line 130
            echo '        ';
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 130)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 130), '')) : ('')).' is-invalid'))]);
            // line 131
            echo '    ';
        }
        // line 132
        $this->displayParentBlock('widget_attributes', $context, $blocks);
    }

    // line 135
    public function block_button_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 136
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 136)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 136), 'btn-secondary')) : ('btn-secondary')).' btn'))]);
        // line 137
        $this->displayParentBlock('button_widget', $context, $blocks);
    }

    // line 140
    public function block_checkbox_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 141
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 141)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 141), '')) : ('')))) : (((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 141)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 141), '')) : (''))));
        // line 142
        if (twig_in_filter('checkbox-custom', ($context['parent_label_class'] ?? null))) {
            // line 143
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 143)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 143), '')) : ('')).' custom-control-input'))]);
            // line 144
            echo '<div class="custom-control custom-checkbox';
            echo (twig_in_filter('checkbox-inline', ($context['parent_label_class'] ?? null))) ? (' custom-control-inline') : ('');
            echo '">';
            // line 145
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
            // line 146
            echo '</div>';
        } else {
            // line 148
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 148)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 148), '')) : ('')).' form-check-input'))]);
            // line 149
            echo '<div class="form-check';
            echo (twig_in_filter('checkbox-inline', ($context['parent_label_class'] ?? null))) ? (' form-check-inline') : ('');
            echo '">';
            // line 150
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
            // line 151
            echo '</div>';
        }
    }

    // line 155
    public function block_radio_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 156
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 156)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 156), '')) : ('')))) : (((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 156)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 156), '')) : (''))));
        // line 157
        if (twig_in_filter('radio-custom', ($context['parent_label_class'] ?? null))) {
            // line 158
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 158)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 158), '')) : ('')).' custom-control-input'))]);
            // line 159
            echo '<div class="custom-control custom-radio';
            echo (twig_in_filter('radio-inline', ($context['parent_label_class'] ?? null))) ? (' custom-control-inline') : ('');
            echo '">';
            // line 160
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
            // line 161
            echo '</div>';
        } else {
            // line 163
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 163)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 163), '')) : ('')).' form-check-input'))]);
            // line 164
            echo '<div class="form-check';
            echo (twig_in_filter('radio-inline', ($context['parent_label_class'] ?? null))) ? (' form-check-inline') : ('');
            echo '">';
            // line 165
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
            // line 166
            echo '</div>';
        }
    }

    // line 170
    public function block_choice_widget_expanded($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 171
        echo '<div ';
        $this->displayBlock('widget_container_attributes', $context, $blocks);
        echo '>';
        // line 172
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['child']) {
            // line 173
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget', ['parent_label_class' => ((twig_get_attribute($this->env, $this->source,             // line 174
($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 174)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 174), '')) : ('')), 'translation_domain' =>             // line 175
($context['choice_translation_domain'] ?? null), 'valid' =>             // line 176
($context['valid'] ?? null), ]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 179
        echo '</div>';
    }

    // line 184
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 185
        if (!(($context['label'] ?? null) === false)) {
            // line 186
            if (((isset($context['compound']) || array_key_exists('compound', $context)) && ($context['compound'] ?? null))) {
                // line 187
                $context['element'] = 'legend';
                // line 188
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 188)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 188), '')) : ('')).' col-form-label'))]);
            } else {
                // line 190
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['for' => ($context['id'] ?? null)]);
            }
            // line 192
            if (($context['required'] ?? null)) {
                // line 193
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 193)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 193), '')) : ('')).' required'))]);
            }
            // line 195
            if (twig_test_empty(($context['label'] ?? null))) {
                // line 196
                if (!twig_test_empty(($context['label_format'] ?? null))) {
                    // line 197
                    $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 198
($context['name'] ?? null), '%id%' =>                     // line 199
($context['id'] ?? null), ]);
                } else {
                    // line 202
                    $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
                }
            }
            // line 205
            echo '<';
            echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'label')) : ('label')), 'html', null, true);
            if (($context['label_attr'] ?? null)) {
                $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = ['attr' => ($context['label_attr'] ?? null)];
                if (!twig_test_iterable($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4)) {
                    throw new RuntimeError('Variables passed to the "with" tag must be a hash.', 205, $this->getSourceContext());
                }
                $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = twig_to_array($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4);
                $context['_parent']                                                          = $context;
                $context                                                                     = $this->env->mergeGlobals(array_merge($context, $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4));
                $this->displayBlock('attributes', $context, $blocks);
                $context = $context['_parent'];
            }
            echo '>';
            echo twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['label'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true);
            $this->displayBlock('form_label_errors', $context, $blocks);
            echo '</';
            echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'label')) : ('label')), 'html', null, true);
            echo '>';
        } else {
            // line 207
            if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
                // line 208
                echo '<div id="';
                echo twig_escape_filter($this->env, ($context['id'] ?? null), 'html', null, true);
                echo '_errors" class="mb-2">';
                // line 209
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
                // line 210
                echo '</div>';
            }
        }
    }

    // line 205
    public function block_form_label_errors($context, array $blocks = [])
    {
        $macros = $this->macros;
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
    }

    // line 215
    public function block_checkbox_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 217
        if ((isset($context['widget']) || array_key_exists('widget', $context))) {
            // line 218
            $context['is_parent_custom'] = ((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context)) && (twig_in_filter('checkbox-custom', ($context['parent_label_class'] ?? null)) || twig_in_filter('radio-custom', ($context['parent_label_class'] ?? null))));
            // line 219
            echo '        ';
            $context['is_custom'] = (twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 219) && (twig_in_filter('checkbox-custom', twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 219)) || twig_in_filter('radio-custom', twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 219))));
            // line 220
            if ((($context['is_parent_custom'] ?? null) || ($context['is_custom'] ?? null))) {
                // line 221
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 221)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 221), '')) : ('')).' custom-control-label'))]);
            } else {
                // line 223
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 223)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 223), '')) : ('')).' form-check-label'))]);
            }
            // line 225
            if (!($context['compound'] ?? null)) {
                // line 226
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['for' => ($context['id'] ?? null)]);
            }
            // line 228
            if (($context['required'] ?? null)) {
                // line 229
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 229)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 229), '')) : ('')).' required'))]);
            }
            // line 231
            if ((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) {
                // line 232
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(twig_replace_filter(((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 232)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 232), '')) : ('')).' ').($context['parent_label_class'] ?? null)), ['checkbox-inline' => '', 'radio-inline' => '', 'checkbox-custom' => '', 'radio-custom' => '']))]);
            }
            // line 234
            if ((!(($context['label'] ?? null) === false) && twig_test_empty(($context['label'] ?? null)))) {
                // line 235
                if (!twig_test_empty(($context['label_format'] ?? null))) {
                    // line 236
                    $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 237
($context['name'] ?? null), '%id%' =>                     // line 238
($context['id'] ?? null), ]);
                } else {
                    // line 241
                    $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
                }
            }
            // line 245
            echo $context['widget'] ?? null;
            echo '
        <label';
            // line 246
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
            // line 247
            ((!(($context['label'] ?? null) === false)) ? (print(twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['label'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true))) : (print('')));
            // line 248
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
            // line 249
            echo '</label>';
        }
    }

    // line 255
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 256
        if (((isset($context['compound']) || array_key_exists('compound', $context)) && ($context['compound'] ?? null))) {
            // line 257
            $context['element'] = 'fieldset';
        }
        // line 259
        echo '<';
        echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'div')) : ('div')), 'html', null, true);
        echo ' class="form-group">';
        // line 260
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 261
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 262
        echo '</';
        echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'div')) : ('div')), 'html', null, true);
        echo '>';
    }

    // line 267
    public function block_form_errors($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 268
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 269
            echo '<span class="';
            if (!Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
                echo 'invalid-feedback';
            } else {
                echo 'alert alert-danger';
            }
            echo ' d-block">';
            // line 270
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['errors'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['error']) {
                // line 271
                echo '<span class="d-block">
                    <span class="form-error-icon badge badge-danger text-uppercase">';
                // line 272
                echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans('Error', [], 'validators'), 'html', null, true);
                echo '</span> <span class="form-error-message">';
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['error'], 'message', [], 'any', false, false, false, 272), 'html', null, true);
                echo '</span>
                </span>';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['error'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 275
            echo '</span>';
        }
    }

    public function getTemplateName()
    {
        return 'bootstrap_4_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [726 => 275,  716 => 272,  713 => 271,  709 => 270,  701 => 269,  699 => 268,  695 => 267,  689 => 262,  687 => 261,  685 => 260,  681 => 259,  678 => 257,  676 => 256,  672 => 255,  667 => 249,  665 => 248,  663 => 247,  649 => 246,  645 => 245,  641 => 241,  638 => 238,  637 => 237,  636 => 236,  634 => 235,  632 => 234,  629 => 232,  627 => 231,  624 => 229,  622 => 228,  619 => 226,  617 => 225,  614 => 223,  611 => 221,  609 => 220,  606 => 219,  604 => 218,  602 => 217,  598 => 215,  591 => 205,  585 => 210,  583 => 209,  579 => 208,  577 => 207,  556 => 205,  552 => 202,  549 => 199,  548 => 198,  547 => 197,  545 => 196,  543 => 195,  540 => 193,  538 => 192,  535 => 190,  532 => 188,  530 => 187,  528 => 186,  526 => 185,  522 => 184,  518 => 179,  512 => 176,  511 => 175,  510 => 174,  509 => 173,  505 => 172,  501 => 171,  497 => 170,  492 => 166,  490 => 165,  486 => 164,  484 => 163,  481 => 161,  479 => 160,  475 => 159,  473 => 158,  471 => 157,  469 => 156,  465 => 155,  460 => 151,  458 => 150,  454 => 149,  452 => 148,  449 => 146,  447 => 145,  443 => 144,  441 => 143,  439 => 142,  437 => 141,  433 => 140,  429 => 137,  427 => 136,  423 => 135,  419 => 132,  416 => 131,  413 => 130,  411 => 129,  407 => 128,  403 => 125,  400 => 123,  398 => 122,  396 => 121,  393 => 119,  391 => 118,  387 => 117,  380 => 111,  378 => 110,  376 => 109,  372 => 108,  367 => 104,  363 => 103,  358 => 100,  354 => 99,  351 => 98,  349 => 97,  344 => 94,  340 => 93,  337 => 92,  335 => 91,  330 => 88,  326 => 87,  323 => 86,  321 => 85,  316 => 82,  312 => 81,  309 => 80,  307 => 79,  302 => 76,  298 => 75,  295 => 74,  293 => 73,  288 => 70,  284 => 69,  281 => 68,  279 => 67,  274 => 64,  270 => 63,  267 => 62,  265 => 61,  261 => 60,  259 => 59,  256 => 57,  254 => 56,  251 => 54,  249 => 53,  247 => 52,  243 => 51,  239 => 48,  236 => 46,  234 => 45,  232 => 44,  228 => 43,  224 => 40,  221 => 38,  219 => 37,  217 => 36,  213 => 35,  209 => 32,  206 => 30,  204 => 29,  202 => 28,  198 => 27,  193 => 23,  190 => 21,  185 => 18,  182 => 17,  180 => 16,  178 => 15,  173 => 12,  170 => 11,  168 => 10,  164 => 9,  162 => 8,  160 => 7,  158 => 6,  154 => 5,  150 => 267,  147 => 266,  144 => 264,  142 => 255,  139 => 254,  136 => 252,  134 => 215,  131 => 214,  129 => 184,  126 => 183,  123 => 181,  121 => 170,  118 => 169,  116 => 155,  113 => 154,  111 => 140,  108 => 139,  106 => 135,  104 => 128,  102 => 117,  99 => 116,  97 => 108,  94 => 107,  92 => 51,  89 => 50,  87 => 43,  84 => 42,  82 => 35,  79 => 34,  77 => 27,  74 => 26,  72 => 5,  69 => 4,  66 => 2,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'bootstrap_4_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/bootstrap_4_layout.html.twig');
    }
}
