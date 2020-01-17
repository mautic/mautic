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

/* foundation_5_layout.html.twig */
class __TwigTemplate_a07af2ddd383c0c7034ad6a19a00f62a1d6d3dd69c6a83092407c3f0e5f02a60 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'form_widget_simple'      => [$this, 'block_form_widget_simple'],
            'textarea_widget'         => [$this, 'block_textarea_widget'],
            'button_widget'           => [$this, 'block_button_widget'],
            'money_widget'            => [$this, 'block_money_widget'],
            'percent_widget'          => [$this, 'block_percent_widget'],
            'datetime_widget'         => [$this, 'block_datetime_widget'],
            'date_widget'             => [$this, 'block_date_widget'],
            'time_widget'             => [$this, 'block_time_widget'],
            'choice_widget_collapsed' => [$this, 'block_choice_widget_collapsed'],
            'choice_widget_expanded'  => [$this, 'block_choice_widget_expanded'],
            'checkbox_widget'         => [$this, 'block_checkbox_widget'],
            'radio_widget'            => [$this, 'block_radio_widget'],
            'form_label'              => [$this, 'block_form_label'],
            'choice_label'            => [$this, 'block_choice_label'],
            'checkbox_label'          => [$this, 'block_checkbox_label'],
            'radio_label'             => [$this, 'block_radio_label'],
            'checkbox_radio_label'    => [$this, 'block_checkbox_radio_label'],
            'form_row'                => [$this, 'block_form_row'],
            'choice_row'              => [$this, 'block_choice_row'],
            'date_row'                => [$this, 'block_date_row'],
            'time_row'                => [$this, 'block_time_row'],
            'datetime_row'            => [$this, 'block_datetime_row'],
            'checkbox_row'            => [$this, 'block_checkbox_row'],
            'radio_row'               => [$this, 'block_radio_row'],
            'form_errors'             => [$this, 'block_form_errors'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return 'form_div_layout.html.twig';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros       = $this->macros;
        $this->parent = $this->loadTemplate('form_div_layout.html.twig', 'foundation_5_layout.html.twig', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 6
    public function block_form_widget_simple($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 7
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 8
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 8)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 8), '')) : ('')).' error'))]);
            // line 9
            echo '    ';
        }
        // line 10
        $this->displayParentBlock('form_widget_simple', $context, $blocks);
    }

    // line 13
    public function block_textarea_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 14
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 15
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 15)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 15), '')) : ('')).' error'))]);
            // line 16
            echo '    ';
        }
        // line 17
        $this->displayParentBlock('textarea_widget', $context, $blocks);
    }

    // line 20
    public function block_button_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 21
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 21)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 21), '')) : ('')).' button'))]);
        // line 22
        $this->displayParentBlock('button_widget', $context, $blocks);
    }

    // line 25
    public function block_money_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 26
        echo '<div class="row collapse">
        ';
        // line 27
        $context['prepend'] = ('{{' == twig_slice($this->env, ($context['money_pattern'] ?? null), 0, 2));
        // line 28
        echo '        ';
        if (!($context['prepend'] ?? null)) {
            // line 29
            echo '            <div class="small-3 large-2 columns">
                <span class="prefix">';
            // line 30
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
            echo '</span>
            </div>
        ';
        }
        // line 33
        echo '        <div class="small-9 large-10 columns">';
        // line 34
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 35
        echo '</div>
        ';
        // line 36
        if (($context['prepend'] ?? null)) {
            // line 37
            echo '            <div class="small-3 large-2 columns">
                <span class="postfix">';
            // line 38
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
            echo '</span>
            </div>
        ';
        }
        // line 41
        echo '    </div>';
    }

    // line 44
    public function block_percent_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 45
        echo '<div class="row collapse">
        <div class="small-9 large-10 columns">';
        // line 47
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 48
        echo '</div>
        <div class="small-3 large-2 columns">
            <span class="postfix">%</span>
        </div>
    </div>';
    }

    // line 55
    public function block_datetime_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 56
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 57
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 59
            echo '        ';
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 59)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 59), '')) : ('')).' row'))]);
            // line 60
            echo '        <div class="row">
            <div class="large-7 columns">';
            // line 61
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 61), 'errors');
            echo '</div>
            <div class="large-5 columns">';
            // line 62
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 62), 'errors');
            echo '</div>
        </div>
        <div ';
            // line 64
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>
            <div class="large-7 columns">';
            // line 65
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 65), 'widget', ['datetime' => true]);
            echo '</div>
            <div class="large-5 columns">';
            // line 66
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 66), 'widget', ['datetime' => true]);
            echo '</div>
        </div>
    ';
        }
    }

    // line 71
    public function block_date_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 72
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 73
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 75
            echo '        ';
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 75)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 75), '')) : ('')).' row'))]);
            // line 76
            echo '        ';
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || !($context['datetime'] ?? null))) {
                // line 77
                echo '            <div ';
                $this->displayBlock('widget_container_attributes', $context, $blocks);
                echo '>
        ';
            }
            // line 79
            echo twig_replace_filter(($context['date_pattern'] ?? null), ['{{ year }}' => (('<div class="large-4 columns">'.             // line 80
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'year', [], 'any', false, false, false, 80), 'widget')).'</div>'), '{{ month }}' => (('<div class="large-4 columns">'.             // line 81
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'month', [], 'any', false, false, false, 81), 'widget')).'</div>'), '{{ day }}' => (('<div class="large-4 columns">'.             // line 82
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'day', [], 'any', false, false, false, 82), 'widget')).'</div>')]);
            // line 84
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || !($context['datetime'] ?? null))) {
                // line 85
                echo '            </div>
        ';
            }
            // line 87
            echo '    ';
        }
    }

    // line 90
    public function block_time_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 91
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 92
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 94
            echo '        ';
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 94)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 94), '')) : ('')).' row'))]);
            // line 95
            echo '        ';
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || (false == ($context['datetime'] ?? null)))) {
                // line 96
                echo '            <div ';
                $this->displayBlock('widget_container_attributes', $context, $blocks);
                echo '>
        ';
            }
            // line 98
            echo '        ';
            if (($context['with_seconds'] ?? null)) {
                // line 99
                echo '            <div class="large-4 columns">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hour', [], 'any', false, false, false, 99), 'widget');
                echo '</div>
            <div class="large-4 columns">
                <div class="row collapse">
                    <div class="small-3 large-2 columns">
                        <span class="prefix">:</span>
                    </div>
                    <div class="small-9 large-10 columns">
                        ';
                // line 106
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minute', [], 'any', false, false, false, 106), 'widget');
                echo '
                    </div>
                </div>
            </div>
            <div class="large-4 columns">
                <div class="row collapse">
                    <div class="small-3 large-2 columns">
                        <span class="prefix">:</span>
                    </div>
                    <div class="small-9 large-10 columns">
                        ';
                // line 116
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'second', [], 'any', false, false, false, 116), 'widget');
                echo '
                    </div>
                </div>
            </div>
        ';
            } else {
                // line 121
                echo '            <div class="large-6 columns">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hour', [], 'any', false, false, false, 121), 'widget');
                echo '</div>
            <div class="large-6 columns">
                <div class="row collapse">
                    <div class="small-3 large-2 columns">
                        <span class="prefix">:</span>
                    </div>
                    <div class="small-9 large-10 columns">
                        ';
                // line 128
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minute', [], 'any', false, false, false, 128), 'widget');
                echo '
                    </div>
                </div>
            </div>
        ';
            }
            // line 133
            echo '        ';
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || (false == ($context['datetime'] ?? null)))) {
                // line 134
                echo '            </div>
        ';
            }
            // line 136
            echo '    ';
        }
    }

    // line 139
    public function block_choice_widget_collapsed($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 140
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 141
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 141)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 141), '')) : ('')).' error'))]);
            // line 142
            echo '    ';
        }
        // line 143
        echo '
    ';
        // line 144
        if (($context['multiple'] ?? null)) {
            // line 145
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['style' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'style', [], 'any', true, true, false, 145)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'style', [], 'any', false, false, false, 145), '')) : ('')).' height: auto; background-image: none;'))]);
            // line 146
            echo '    ';
        }
        // line 147
        echo '
    ';
        // line 148
        if ((((($context['required'] ?? null) && (null === ($context['placeholder'] ?? null))) && !($context['placeholder_in_choices'] ?? null)) && !($context['multiple'] ?? null))) {
            // line 149
            $context['required'] = false;
        }
        // line 151
        echo '<select ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        if (($context['multiple'] ?? null)) {
            echo ' multiple="multiple" data-customforms="disabled"';
        }
        echo '>
        ';
        // line 152
        if (!(null === ($context['placeholder'] ?? null))) {
            // line 153
            echo '<option value=""';
            if ((($context['required'] ?? null) && twig_test_empty(($context['value'] ?? null)))) {
                echo ' selected="selected"';
            }
            echo '>';
            echo twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['placeholder'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['placeholder'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true);
            echo '</option>';
        }
        // line 155
        if ((twig_length_filter($this->env, ($context['preferred_choices'] ?? null)) > 0)) {
            // line 156
            $context['options'] = ($context['preferred_choices'] ?? null);
            // line 157
            $this->displayBlock('choice_widget_options', $context, $blocks);
            // line 158
            if (((twig_length_filter($this->env, ($context['choices'] ?? null)) > 0) && !(null === ($context['separator'] ?? null)))) {
                // line 159
                echo '<option disabled="disabled">';
                echo twig_escape_filter($this->env, ($context['separator'] ?? null), 'html', null, true);
                echo '</option>';
            }
        }
        // line 162
        $context['options'] = ($context['choices'] ?? null);
        // line 163
        $this->displayBlock('choice_widget_options', $context, $blocks);
        // line 164
        echo '</select>';
    }

    // line 167
    public function block_choice_widget_expanded($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 168
        if (twig_in_filter('-inline', ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 168)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 168), '')) : ('')))) {
            // line 169
            echo '        <ul class="inline-list">
            ';
            // line 170
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['child']) {
                // line 171
                echo '                <li>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget', ['parent_label_class' => ((twig_get_attribute($this->env, $this->source,                 // line 172
($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 172)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 172), '')) : (''))]);
                // line 173
                echo '</li>
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 175
            echo '        </ul>
    ';
        } else {
            // line 177
            echo '        <div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>
            ';
            // line 178
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['child']) {
                // line 179
                echo '                ';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget', ['parent_label_class' => ((twig_get_attribute($this->env, $this->source,                 // line 180
($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 180)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 180), '')) : (''))]);
                // line 181
                echo '
            ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 183
            echo '        </div>
    ';
        }
    }

    // line 187
    public function block_checkbox_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 188
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), '')) : (''));
        // line 189
        echo '    ';
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 190
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 190)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 190), '')) : ('')).' error'))]);
            // line 191
            echo '    ';
        }
        // line 192
        echo '    ';
        if (twig_in_filter('checkbox-inline', ($context['parent_label_class'] ?? null))) {
            // line 193
            echo '        ';
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
            echo '
    ';
        } else {
            // line 195
            echo '        <div class="checkbox">
            ';
            // line 196
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('checkbox_widget', $context, $blocks)]);
            echo '
        </div>
    ';
        }
    }

    // line 201
    public function block_radio_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 202
        $context['parent_label_class'] = (((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) ? (_twig_default_filter(($context['parent_label_class'] ?? null), '')) : (''));
        // line 203
        echo '    ';
        if (twig_in_filter('radio-inline', ($context['parent_label_class'] ?? null))) {
            // line 204
            echo '        ';
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
            echo '
    ';
        } else {
            // line 206
            echo '        ';
            if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
                // line 207
                $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 207)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 207), '')) : ('')).' error'))]);
                // line 208
                echo '        ';
            }
            // line 209
            echo '        <div class="radio">
            ';
            // line 210
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label', ['widget' => $this->renderParentBlock('radio_widget', $context, $blocks)]);
            echo '
        </div>
    ';
        }
    }

    // line 217
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 218
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 219
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 219)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 219), '')) : ('')).' error'))]);
            // line 220
            echo '    ';
        }
        // line 221
        $this->displayParentBlock('form_label', $context, $blocks);
    }

    // line 224
    public function block_choice_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 225
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 226
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 226)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 226), '')) : ('')).' error'))]);
            // line 227
            echo '    ';
        }
        // line 228
        echo '    ';
        // line 229
        echo '    ';
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(twig_replace_filter(((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 229)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 229), '')) : ('')), ['checkbox-inline' => '', 'radio-inline' => '']))]);
        // line 230
        $this->displayBlock('form_label', $context, $blocks);
    }

    // line 233
    public function block_checkbox_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 234
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 237
    public function block_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 238
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 241
    public function block_checkbox_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 242
        if (($context['required'] ?? null)) {
            // line 243
            echo '        ';
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 243)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 243), '')) : ('')).' required'))]);
            // line 244
            echo '    ';
        }
        // line 245
        echo '    ';
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 246
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 246)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 246), '')) : ('')).' error'))]);
            // line 247
            echo '    ';
        }
        // line 248
        echo '    ';
        if ((isset($context['parent_label_class']) || array_key_exists('parent_label_class', $context))) {
            // line 249
            echo '        ';
            $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 249)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 249), '')) : ('')).($context['parent_label_class'] ?? null)))]);
            // line 250
            echo '    ';
        }
        // line 251
        echo '    ';
        if (twig_test_empty(($context['label'] ?? null))) {
            // line 252
            if (!twig_test_empty(($context['label_format'] ?? null))) {
                // line 253
                $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 254
($context['name'] ?? null), '%id%' =>                 // line 255
($context['id'] ?? null), ]);
            } else {
                // line 258
                $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
            }
        }
        // line 261
        echo '    <label';
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
        echo '>
        ';
        // line 262
        echo $context['widget'] ?? null;
        echo '
        ';
        // line 263
        echo twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['label'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true);
        echo '
    </label>';
    }

    // line 269
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 270
        echo '<div class="row">
        <div class="large-12 columns';
        // line 271
        if (((!($context['compound'] ?? null) || (((isset($context['force_error']) || array_key_exists('force_error', $context))) ? (_twig_default_filter(($context['force_error'] ?? null), false)) : (false))) && !($context['valid'] ?? null))) {
            echo ' error';
        }
        echo '">
            ';
        // line 272
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        echo '
            ';
        // line 273
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        echo '
            ';
        // line 274
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        echo '
        </div>
    </div>';
    }

    // line 279
    public function block_choice_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 280
        $context['force_error'] = true;
        // line 281
        echo '    ';
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 284
    public function block_date_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 285
        $context['force_error'] = true;
        // line 286
        echo '    ';
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 289
    public function block_time_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 290
        $context['force_error'] = true;
        // line 291
        echo '    ';
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 294
    public function block_datetime_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 295
        $context['force_error'] = true;
        // line 296
        echo '    ';
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 299
    public function block_checkbox_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 300
        echo '<div class="row">
        <div class="large-12 columns';
        // line 301
        if (!($context['valid'] ?? null)) {
            echo ' error';
        }
        echo '">
            ';
        // line 302
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        echo '
            ';
        // line 303
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        echo '
        </div>
    </div>';
    }

    // line 308
    public function block_radio_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 309
        echo '<div class="row">
        <div class="large-12 columns';
        // line 310
        if (!($context['valid'] ?? null)) {
            echo ' error';
        }
        echo '">
            ';
        // line 311
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        echo '
            ';
        // line 312
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        echo '
        </div>
    </div>';
    }

    // line 319
    public function block_form_errors($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 320
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 321
            if (!Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
                echo '<small class="error">';
            } else {
                echo '<div data-alert class="alert-box alert">';
            }
            // line 322
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['errors'] ?? null));
            $context['loop']    = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length                       = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex']  = $length;
                $context['loop']['length']    = $length;
                $context['loop']['last']      = 1 === $length;
            }
            foreach ($context['_seq'] as $context['_key'] => $context['error']) {
                // line 323
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['error'], 'message', [], 'any', false, false, false, 323), 'html', null, true);
                echo '
            ';
                // line 324
                if (!twig_get_attribute($this->env, $this->source, $context['loop'], 'last', [], 'any', false, false, false, 324)) {
                    echo ', ';
                }
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['error'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 326
            if (!Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
                echo '</small>';
            } else {
                echo '</div>';
            }
        }
    }

    public function getTemplateName()
    {
        return 'foundation_5_layout.html.twig';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [815 => 326,  799 => 324,  795 => 323,  778 => 322,  772 => 321,  770 => 320,  766 => 319,  759 => 312,  755 => 311,  749 => 310,  746 => 309,  742 => 308,  735 => 303,  731 => 302,  725 => 301,  722 => 300,  718 => 299,  713 => 296,  711 => 295,  707 => 294,  702 => 291,  700 => 290,  696 => 289,  691 => 286,  689 => 285,  685 => 284,  680 => 281,  678 => 280,  674 => 279,  667 => 274,  663 => 273,  659 => 272,  653 => 271,  650 => 270,  646 => 269,  640 => 263,  636 => 262,  620 => 261,  616 => 258,  613 => 255,  612 => 254,  611 => 253,  609 => 252,  606 => 251,  603 => 250,  600 => 249,  597 => 248,  594 => 247,  592 => 246,  589 => 245,  586 => 244,  583 => 243,  581 => 242,  577 => 241,  573 => 238,  569 => 237,  565 => 234,  561 => 233,  557 => 230,  554 => 229,  552 => 228,  549 => 227,  547 => 226,  545 => 225,  541 => 224,  537 => 221,  534 => 220,  532 => 219,  530 => 218,  526 => 217,  518 => 210,  515 => 209,  512 => 208,  510 => 207,  507 => 206,  501 => 204,  498 => 203,  496 => 202,  492 => 201,  484 => 196,  481 => 195,  475 => 193,  472 => 192,  469 => 191,  467 => 190,  464 => 189,  462 => 188,  458 => 187,  452 => 183,  445 => 181,  443 => 180,  441 => 179,  437 => 178,  432 => 177,  428 => 175,  421 => 173,  419 => 172,  417 => 171,  413 => 170,  410 => 169,  408 => 168,  404 => 167,  400 => 164,  398 => 163,  396 => 162,  390 => 159,  388 => 158,  386 => 157,  384 => 156,  382 => 155,  373 => 153,  371 => 152,  363 => 151,  360 => 149,  358 => 148,  355 => 147,  352 => 146,  350 => 145,  348 => 144,  345 => 143,  342 => 142,  340 => 141,  338 => 140,  334 => 139,  329 => 136,  325 => 134,  322 => 133,  314 => 128,  303 => 121,  295 => 116,  282 => 106,  271 => 99,  268 => 98,  262 => 96,  259 => 95,  256 => 94,  253 => 92,  251 => 91,  247 => 90,  242 => 87,  238 => 85,  236 => 84,  234 => 82,  233 => 81,  232 => 80,  231 => 79,  225 => 77,  222 => 76,  219 => 75,  216 => 73,  214 => 72,  210 => 71,  202 => 66,  198 => 65,  194 => 64,  189 => 62,  185 => 61,  182 => 60,  179 => 59,  176 => 57,  174 => 56,  170 => 55,  162 => 48,  160 => 47,  157 => 45,  153 => 44,  149 => 41,  143 => 38,  140 => 37,  138 => 36,  135 => 35,  133 => 34,  131 => 33,  125 => 30,  122 => 29,  119 => 28,  117 => 27,  114 => 26,  110 => 25,  106 => 22,  104 => 21,  100 => 20,  96 => 17,  93 => 16,  91 => 15,  89 => 14,  85 => 13,  81 => 10,  78 => 9,  76 => 8,  74 => 7,  70 => 6,  59 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'foundation_5_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/foundation_5_layout.html.twig');
    }
}
