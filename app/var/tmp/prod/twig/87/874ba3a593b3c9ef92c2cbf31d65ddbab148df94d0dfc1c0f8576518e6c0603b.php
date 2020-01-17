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

/* form_div_layout.html.twig */
class __TwigTemplate_6358e91a968c3a718a6b97d33b8606d1db5a69af1062c146b05a0e78b4f170fb extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'form_widget'                 => [$this, 'block_form_widget'],
            'form_widget_simple'          => [$this, 'block_form_widget_simple'],
            'form_widget_compound'        => [$this, 'block_form_widget_compound'],
            'collection_widget'           => [$this, 'block_collection_widget'],
            'textarea_widget'             => [$this, 'block_textarea_widget'],
            'choice_widget'               => [$this, 'block_choice_widget'],
            'choice_widget_expanded'      => [$this, 'block_choice_widget_expanded'],
            'choice_widget_collapsed'     => [$this, 'block_choice_widget_collapsed'],
            'choice_widget_options'       => [$this, 'block_choice_widget_options'],
            'checkbox_widget'             => [$this, 'block_checkbox_widget'],
            'radio_widget'                => [$this, 'block_radio_widget'],
            'datetime_widget'             => [$this, 'block_datetime_widget'],
            'date_widget'                 => [$this, 'block_date_widget'],
            'time_widget'                 => [$this, 'block_time_widget'],
            'dateinterval_widget'         => [$this, 'block_dateinterval_widget'],
            'number_widget'               => [$this, 'block_number_widget'],
            'integer_widget'              => [$this, 'block_integer_widget'],
            'money_widget'                => [$this, 'block_money_widget'],
            'url_widget'                  => [$this, 'block_url_widget'],
            'search_widget'               => [$this, 'block_search_widget'],
            'percent_widget'              => [$this, 'block_percent_widget'],
            'password_widget'             => [$this, 'block_password_widget'],
            'hidden_widget'               => [$this, 'block_hidden_widget'],
            'email_widget'                => [$this, 'block_email_widget'],
            'range_widget'                => [$this, 'block_range_widget'],
            'button_widget'               => [$this, 'block_button_widget'],
            'submit_widget'               => [$this, 'block_submit_widget'],
            'reset_widget'                => [$this, 'block_reset_widget'],
            'tel_widget'                  => [$this, 'block_tel_widget'],
            'color_widget'                => [$this, 'block_color_widget'],
            'form_label'                  => [$this, 'block_form_label'],
            'button_label'                => [$this, 'block_button_label'],
            'repeated_row'                => [$this, 'block_repeated_row'],
            'form_row'                    => [$this, 'block_form_row'],
            'button_row'                  => [$this, 'block_button_row'],
            'hidden_row'                  => [$this, 'block_hidden_row'],
            'form'                        => [$this, 'block_form'],
            'form_start'                  => [$this, 'block_form_start'],
            'form_end'                    => [$this, 'block_form_end'],
            'form_errors'                 => [$this, 'block_form_errors'],
            'form_rest'                   => [$this, 'block_form_rest'],
            'form_rows'                   => [$this, 'block_form_rows'],
            'widget_attributes'           => [$this, 'block_widget_attributes'],
            'widget_container_attributes' => [$this, 'block_widget_container_attributes'],
            'button_attributes'           => [$this, 'block_button_attributes'],
            'attributes'                  => [$this, 'block_attributes'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        $this->displayBlock('form_widget', $context, $blocks);
        // line 11
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 16
        $this->displayBlock('form_widget_compound', $context, $blocks);
        // line 26
        $this->displayBlock('collection_widget', $context, $blocks);
        // line 33
        $this->displayBlock('textarea_widget', $context, $blocks);
        // line 37
        $this->displayBlock('choice_widget', $context, $blocks);
        // line 45
        $this->displayBlock('choice_widget_expanded', $context, $blocks);
        // line 54
        $this->displayBlock('choice_widget_collapsed', $context, $blocks);
        // line 74
        $this->displayBlock('choice_widget_options', $context, $blocks);
        // line 87
        $this->displayBlock('checkbox_widget', $context, $blocks);
        // line 91
        $this->displayBlock('radio_widget', $context, $blocks);
        // line 95
        $this->displayBlock('datetime_widget', $context, $blocks);
        // line 108
        $this->displayBlock('date_widget', $context, $blocks);
        // line 122
        $this->displayBlock('time_widget', $context, $blocks);
        // line 133
        $this->displayBlock('dateinterval_widget', $context, $blocks);
        // line 168
        $this->displayBlock('number_widget', $context, $blocks);
        // line 174
        $this->displayBlock('integer_widget', $context, $blocks);
        // line 179
        $this->displayBlock('money_widget', $context, $blocks);
        // line 183
        $this->displayBlock('url_widget', $context, $blocks);
        // line 188
        $this->displayBlock('search_widget', $context, $blocks);
        // line 193
        $this->displayBlock('percent_widget', $context, $blocks);
        // line 198
        $this->displayBlock('password_widget', $context, $blocks);
        // line 203
        $this->displayBlock('hidden_widget', $context, $blocks);
        // line 208
        $this->displayBlock('email_widget', $context, $blocks);
        // line 213
        $this->displayBlock('range_widget', $context, $blocks);
        // line 218
        $this->displayBlock('button_widget', $context, $blocks);
        // line 234
        $this->displayBlock('submit_widget', $context, $blocks);
        // line 239
        $this->displayBlock('reset_widget', $context, $blocks);
        // line 244
        $this->displayBlock('tel_widget', $context, $blocks);
        // line 249
        $this->displayBlock('color_widget', $context, $blocks);
        // line 256
        $this->displayBlock('form_label', $context, $blocks);
        // line 284
        $this->displayBlock('button_label', $context, $blocks);
        // line 288
        $this->displayBlock('repeated_row', $context, $blocks);
        // line 296
        $this->displayBlock('form_row', $context, $blocks);
        // line 304
        $this->displayBlock('button_row', $context, $blocks);
        // line 310
        $this->displayBlock('hidden_row', $context, $blocks);
        // line 316
        $this->displayBlock('form', $context, $blocks);
        // line 322
        $this->displayBlock('form_start', $context, $blocks);
        // line 336
        $this->displayBlock('form_end', $context, $blocks);
        // line 343
        $this->displayBlock('form_errors', $context, $blocks);
        // line 353
        $this->displayBlock('form_rest', $context, $blocks);
        // line 374
        echo '
';
        // line 377
        $this->displayBlock('form_rows', $context, $blocks);
        // line 383
        $this->displayBlock('widget_attributes', $context, $blocks);
        // line 390
        $this->displayBlock('widget_container_attributes', $context, $blocks);
        // line 395
        $this->displayBlock('button_attributes', $context, $blocks);
        // line 400
        $this->displayBlock('attributes', $context, $blocks);
    }

    // line 3
    public function block_form_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        if (($context['compound'] ?? null)) {
            // line 5
            $this->displayBlock('form_widget_compound', $context, $blocks);
        } else {
            // line 7
            $this->displayBlock('form_widget_simple', $context, $blocks);
        }
    }

    // line 11
    public function block_form_widget_simple($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 12
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'text')) : ('text'));
        // line 13
        echo '<input type="';
        echo twig_escape_filter($this->env, ($context['type'] ?? null), 'html', null, true);
        echo '" ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        echo ' ';
        if (!twig_test_empty(($context['value'] ?? null))) {
            echo 'value="';
            echo twig_escape_filter($this->env, ($context['value'] ?? null), 'html', null, true);
            echo '" ';
        }
        echo '/>';
    }

    // line 16
    public function block_form_widget_compound($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 17
        echo '<div ';
        $this->displayBlock('widget_container_attributes', $context, $blocks);
        echo '>';
        // line 18
        if (Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null))) {
            // line 19
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        }
        // line 21
        $this->displayBlock('form_rows', $context, $blocks);
        // line 22
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'rest');
        // line 23
        echo '</div>';
    }

    // line 26
    public function block_collection_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 27
        if ((isset($context['prototype']) || array_key_exists('prototype', $context))) {
            // line 28
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['data-prototype' => $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['prototype'] ?? null), 'row')]);
        }
        // line 30
        $this->displayBlock('form_widget', $context, $blocks);
    }

    // line 33
    public function block_textarea_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 34
        echo '<textarea ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        echo '>';
        echo twig_escape_filter($this->env, ($context['value'] ?? null), 'html', null, true);
        echo '</textarea>';
    }

    // line 37
    public function block_choice_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 38
        if (($context['expanded'] ?? null)) {
            // line 39
            $this->displayBlock('choice_widget_expanded', $context, $blocks);
        } else {
            // line 41
            $this->displayBlock('choice_widget_collapsed', $context, $blocks);
        }
    }

    // line 45
    public function block_choice_widget_expanded($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 46
        echo '<div ';
        $this->displayBlock('widget_container_attributes', $context, $blocks);
        echo '>';
        // line 47
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['child']) {
            // line 48
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget');
            // line 49
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'label', ['translation_domain' => ($context['choice_translation_domain'] ?? null)]);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 51
        echo '</div>';
    }

    // line 54
    public function block_choice_widget_collapsed($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 55
        if (((((($context['required'] ?? null) && (null === ($context['placeholder'] ?? null))) && !($context['placeholder_in_choices'] ?? null)) && !($context['multiple'] ?? null)) && (!twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'size', [], 'any', true, true, false, 55) || (twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'size', [], 'any', false, false, false, 55) <= 1)))) {
            // line 56
            $context['required'] = false;
        }
        // line 58
        echo '<select ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        if (($context['multiple'] ?? null)) {
            echo ' multiple="multiple"';
        }
        echo '>';
        // line 59
        if (!(null === ($context['placeholder'] ?? null))) {
            // line 60
            echo '<option value=""';
            if ((($context['required'] ?? null) && twig_test_empty(($context['value'] ?? null)))) {
                echo ' selected="selected"';
            }
            echo '>';
            (((($context['placeholder'] ?? null) != '')) ? (print(twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['placeholder'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['placeholder'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true))) : (print('')));
            echo '</option>';
        }
        // line 62
        if ((twig_length_filter($this->env, ($context['preferred_choices'] ?? null)) > 0)) {
            // line 63
            $context['options'] = ($context['preferred_choices'] ?? null);
            // line 64
            $this->displayBlock('choice_widget_options', $context, $blocks);
            // line 65
            if (((twig_length_filter($this->env, ($context['choices'] ?? null)) > 0) && !(null === ($context['separator'] ?? null)))) {
                // line 66
                echo '<option disabled="disabled">';
                echo twig_escape_filter($this->env, ($context['separator'] ?? null), 'html', null, true);
                echo '</option>';
            }
        }
        // line 69
        $context['options'] = ($context['choices'] ?? null);
        // line 70
        $this->displayBlock('choice_widget_options', $context, $blocks);
        // line 71
        echo '</select>';
    }

    // line 74
    public function block_choice_widget_options($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 75
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['options'] ?? null));
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
        foreach ($context['_seq'] as $context['group_label'] => $context['choice']) {
            // line 76
            if (twig_test_iterable($context['choice'])) {
                // line 77
                echo '<optgroup label="';
                echo twig_escape_filter($this->env, (((($context['choice_translation_domain'] ?? null) === false)) ? ($context['group_label']) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans($context['group_label'], [], ($context['choice_translation_domain'] ?? null)))), 'html', null, true);
                echo '">
                ';
                // line 78
                $context['options'] = $context['choice'];
                // line 79
                $this->displayBlock('choice_widget_options', $context, $blocks);
                // line 80
                echo '</optgroup>';
            } else {
                // line 82
                echo '<option value="';
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['choice'], 'value', [], 'any', false, false, false, 82), 'html', null, true);
                echo '"';
                if (twig_get_attribute($this->env, $this->source, $context['choice'], 'attr', [], 'any', false, false, false, 82)) {
                    $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ['attr' => twig_get_attribute($this->env, $this->source, $context['choice'], 'attr', [], 'any', false, false, false, 82)];
                    if (!twig_test_iterable($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4)) {
                        throw new RuntimeError('Variables passed to the "with" tag must be a hash.', 82, $this->getSourceContext());
                    }
                    $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = twig_to_array($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4);
                    $context['_parent']                                                          = $context;
                    $context                                                                     = $this->env->mergeGlobals(array_merge($context, $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4));
                    $this->displayBlock('attributes', $context, $blocks);
                    $context = $context['_parent'];
                }
                if (Symfony\Bridge\Twig\Extension\twig_is_selected_choice($context['choice'], ($context['value'] ?? null))) {
                    echo ' selected="selected"';
                }
                echo '>';
                echo twig_escape_filter($this->env, (((($context['choice_translation_domain'] ?? null) === false)) ? (twig_get_attribute($this->env, $this->source, $context['choice'], 'label', [], 'any', false, false, false, 82)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(twig_get_attribute($this->env, $this->source, $context['choice'], 'label', [], 'any', false, false, false, 82), [], ($context['choice_translation_domain'] ?? null)))), 'html', null, true);
                echo '</option>';
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
        unset($context['_seq'], $context['_iterated'], $context['group_label'], $context['choice'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    // line 87
    public function block_checkbox_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 88
        echo '<input type="checkbox" ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        if ((isset($context['value']) || array_key_exists('value', $context))) {
            echo ' value="';
            echo twig_escape_filter($this->env, ($context['value'] ?? null), 'html', null, true);
            echo '"';
        }
        if (($context['checked'] ?? null)) {
            echo ' checked="checked"';
        }
        echo ' />';
    }

    // line 91
    public function block_radio_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 92
        echo '<input type="radio" ';
        $this->displayBlock('widget_attributes', $context, $blocks);
        if ((isset($context['value']) || array_key_exists('value', $context))) {
            echo ' value="';
            echo twig_escape_filter($this->env, ($context['value'] ?? null), 'html', null, true);
            echo '"';
        }
        if (($context['checked'] ?? null)) {
            echo ' checked="checked"';
        }
        echo ' />';
    }

    // line 95
    public function block_datetime_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 96
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 97
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 99
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 100
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 100), 'errors');
            // line 101
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 101), 'errors');
            // line 102
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 102), 'widget');
            // line 103
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 103), 'widget');
            // line 104
            echo '</div>';
        }
    }

    // line 108
    public function block_date_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 109
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 110
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 112
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 113
            echo twig_replace_filter(($context['date_pattern'] ?? null), ['{{ year }}' => // line 114
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'year', [], 'any', false, false, false, 114), 'widget'), '{{ month }}' =>             // line 115
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'month', [], 'any', false, false, false, 115), 'widget'), '{{ day }}' =>             // line 116
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'day', [], 'any', false, false, false, 116), 'widget'), ]);
            // line 118
            echo '</div>';
        }
    }

    // line 122
    public function block_time_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 123
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 124
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 126
            $context['vars'] = (((($context['widget'] ?? null) == 'text')) ? (['attr' => ['size' => 1]]) : ([]));
            // line 127
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>
            ';
            // line 128
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hour', [], 'any', false, false, false, 128), 'widget', ($context['vars'] ?? null));
            if (($context['with_minutes'] ?? null)) {
                echo ':';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minute', [], 'any', false, false, false, 128), 'widget', ($context['vars'] ?? null));
            }
            if (($context['with_seconds'] ?? null)) {
                echo ':';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'second', [], 'any', false, false, false, 128), 'widget', ($context['vars'] ?? null));
            }
            // line 129
            echo '        </div>';
        }
    }

    // line 133
    public function block_dateinterval_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 134
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 135
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 137
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 138
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
            // line 139
            echo '<table class="';
            echo twig_escape_filter($this->env, (((isset($context['table_class']) || array_key_exists('table_class', $context))) ? (_twig_default_filter(($context['table_class'] ?? null), '')) : ('')), 'html', null, true);
            echo '" role="presentation">
                <thead>
                    <tr>';
            // line 142
            if (($context['with_years'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 142), 'label');
                echo '</th>';
            }
            // line 143
            if (($context['with_months'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 143), 'label');
                echo '</th>';
            }
            // line 144
            if (($context['with_weeks'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 144), 'label');
                echo '</th>';
            }
            // line 145
            if (($context['with_days'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 145), 'label');
                echo '</th>';
            }
            // line 146
            if (($context['with_hours'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 146), 'label');
                echo '</th>';
            }
            // line 147
            if (($context['with_minutes'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 147), 'label');
                echo '</th>';
            }
            // line 148
            if (($context['with_seconds'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 148), 'label');
                echo '</th>';
            }
            // line 149
            echo '</tr>
                </thead>
                <tbody>
                    <tr>';
            // line 153
            if (($context['with_years'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 153), 'widget');
                echo '</td>';
            }
            // line 154
            if (($context['with_months'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 154), 'widget');
                echo '</td>';
            }
            // line 155
            if (($context['with_weeks'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 155), 'widget');
                echo '</td>';
            }
            // line 156
            if (($context['with_days'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 156), 'widget');
                echo '</td>';
            }
            // line 157
            if (($context['with_hours'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 157), 'widget');
                echo '</td>';
            }
            // line 158
            if (($context['with_minutes'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 158), 'widget');
                echo '</td>';
            }
            // line 159
            if (($context['with_seconds'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 159), 'widget');
                echo '</td>';
            }
            // line 160
            echo '</tr>
                </tbody>
            </table>';
            // line 163
            if (($context['with_invert'] ?? null)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'invert', [], 'any', false, false, false, 163), 'widget');
            }
            // line 164
            echo '</div>';
        }
    }

    // line 168
    public function block_number_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 170
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'text')) : ('text'));
        // line 171
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 174
    public function block_integer_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 175
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'number')) : ('number'));
        // line 176
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 179
    public function block_money_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 180
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null), $this->renderBlock('form_widget_simple', $context, $blocks));
    }

    // line 183
    public function block_url_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 184
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'url')) : ('url'));
        // line 185
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 188
    public function block_search_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 189
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'search')) : ('search'));
        // line 190
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 193
    public function block_percent_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 194
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'text')) : ('text'));
        // line 195
        $this->displayBlock('form_widget_simple', $context, $blocks);
        echo ' %';
    }

    // line 198
    public function block_password_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 199
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'password')) : ('password'));
        // line 200
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 203
    public function block_hidden_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 204
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'hidden')) : ('hidden'));
        // line 205
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 208
    public function block_email_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 209
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'email')) : ('email'));
        // line 210
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 213
    public function block_range_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 214
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'range')) : ('range'));
        // line 215
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 218
    public function block_button_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 219
        if (twig_test_empty(($context['label'] ?? null))) {
            // line 220
            if (!twig_test_empty(($context['label_format'] ?? null))) {
                // line 221
                $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 222
($context['name'] ?? null), '%id%' =>                 // line 223
($context['id'] ?? null), ]);
            } elseif ((            // line 225
($context['label'] ?? null) === false)) {
                // line 226
                $context['translation_domain'] = false;
            } else {
                // line 228
                $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
            }
        }
        // line 231
        echo '<button type="';
        echo twig_escape_filter($this->env, (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'button')) : ('button')), 'html', null, true);
        echo '" ';
        $this->displayBlock('button_attributes', $context, $blocks);
        echo '>';
        echo twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? (($context['label'] ?? null)) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)))), 'html', null, true);
        echo '</button>';
    }

    // line 234
    public function block_submit_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 235
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'submit')) : ('submit'));
        // line 236
        $this->displayBlock('button_widget', $context, $blocks);
    }

    // line 239
    public function block_reset_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 240
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'reset')) : ('reset'));
        // line 241
        $this->displayBlock('button_widget', $context, $blocks);
    }

    // line 244
    public function block_tel_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 245
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'tel')) : ('tel'));
        // line 246
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 249
    public function block_color_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 250
        $context['type'] = (((isset($context['type']) || array_key_exists('type', $context))) ? (_twig_default_filter(($context['type'] ?? null), 'color')) : ('color'));
        // line 251
        $this->displayBlock('form_widget_simple', $context, $blocks);
    }

    // line 256
    public function block_form_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 257
        if (!(($context['label'] ?? null) === false)) {
            // line 258
            if (!($context['compound'] ?? null)) {
                // line 259
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['for' => ($context['id'] ?? null)]);
            }
            // line 261
            if (($context['required'] ?? null)) {
                // line 262
                $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 262)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 262), '')) : ('')).' required'))]);
            }
            // line 264
            if (twig_test_empty(($context['label'] ?? null))) {
                // line 265
                if (!twig_test_empty(($context['label_format'] ?? null))) {
                    // line 266
                    $context['label'] = twig_replace_filter(($context['label_format'] ?? null), ['%name%' => // line 267
($context['name'] ?? null), '%id%' =>                     // line 268
($context['id'] ?? null), ]);
                } else {
                    // line 271
                    $context['label'] = $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->humanize(($context['name'] ?? null));
                }
            }
            // line 274
            echo '<';
            echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'label')) : ('label')), 'html', null, true);
            if (($context['label_attr'] ?? null)) {
                $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = ['attr' => ($context['label_attr'] ?? null)];
                if (!twig_test_iterable($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144)) {
                    throw new RuntimeError('Variables passed to the "with" tag must be a hash.', 274, $this->getSourceContext());
                }
                $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = twig_to_array($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144);
                $context['_parent']                                                          = $context;
                $context                                                                     = $this->env->mergeGlobals(array_merge($context, $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144));
                $this->displayBlock('attributes', $context, $blocks);
                $context = $context['_parent'];
            }
            echo '>';
            // line 275
            if ((($context['translation_domain'] ?? null) === false)) {
                // line 276
                echo twig_escape_filter($this->env, ($context['label'] ?? null), 'html', null, true);
            } else {
                // line 278
                echo twig_escape_filter($this->env, $this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans(($context['label'] ?? null), [], ($context['translation_domain'] ?? null)), 'html', null, true);
            }
            // line 280
            echo '</';
            echo twig_escape_filter($this->env, (((isset($context['element']) || array_key_exists('element', $context))) ? (_twig_default_filter(($context['element'] ?? null), 'label')) : ('label')), 'html', null, true);
            echo '>';
        }
    }

    // line 284
    public function block_button_label($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    // line 288
    public function block_repeated_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 293
        $this->displayBlock('form_rows', $context, $blocks);
    }

    // line 296
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 297
        echo '<div>';
        // line 298
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 299
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 300
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 301
        echo '</div>';
    }

    // line 304
    public function block_button_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 305
        echo '<div>';
        // line 306
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 307
        echo '</div>';
    }

    // line 310
    public function block_hidden_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 311
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
    }

    // line 316
    public function block_form($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 317
        echo         $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderBlock(($context['form'] ?? null), 'form_start');
        // line 318
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 319
        echo         $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderBlock(($context['form'] ?? null), 'form_end');
    }

    // line 322
    public function block_form_start($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 323
        twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'setMethodRendered', [], 'method', false, false, false, 323);
        // line 324
        $context['method'] = twig_upper_filter($this->env, ($context['method'] ?? null));
        // line 325
        if (twig_in_filter(($context['method'] ?? null), [0 => 'GET', 1 => 'POST'])) {
            // line 326
            $context['form_method'] = ($context['method'] ?? null);
        } else {
            // line 328
            $context['form_method'] = 'POST';
        }
        // line 330
        echo '<form';
        if ((($context['name'] ?? null) != '')) {
            echo ' name="';
            echo twig_escape_filter($this->env, ($context['name'] ?? null), 'html', null, true);
            echo '"';
        }
        echo ' method="';
        echo twig_escape_filter($this->env, twig_lower_filter($this->env, ($context['form_method'] ?? null)), 'html', null, true);
        echo '"';
        if ((($context['action'] ?? null) != '')) {
            echo ' action="';
            echo twig_escape_filter($this->env, ($context['action'] ?? null), 'html', null, true);
            echo '"';
        }
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['attr'] ?? null));
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
        if (($context['multipart'] ?? null)) {
            echo ' enctype="multipart/form-data"';
        }
        echo '>';
        // line 331
        if ((($context['form_method'] ?? null) != ($context['method'] ?? null))) {
            // line 332
            echo '<input type="hidden" name="_method" value="';
            echo twig_escape_filter($this->env, ($context['method'] ?? null), 'html', null, true);
            echo '" />';
        }
    }

    // line 336
    public function block_form_end($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 337
        if ((!(isset($context['render_rest']) || array_key_exists('render_rest', $context)) || ($context['render_rest'] ?? null))) {
            // line 338
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'rest');
        }
        // line 340
        echo '</form>';
    }

    // line 343
    public function block_form_errors($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 344
        if ((twig_length_filter($this->env, ($context['errors'] ?? null)) > 0)) {
            // line 345
            echo '<ul>';
            // line 346
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['errors'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['error']) {
                // line 347
                echo '<li>';
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context['error'], 'message', [], 'any', false, false, false, 347), 'html', null, true);
                echo '</li>';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['error'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 349
            echo '</ul>';
        }
    }

    // line 353
    public function block_form_rest($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 354
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['child']) {
            // line 355
            if (!twig_get_attribute($this->env, $this->source, $context['child'], 'rendered', [], 'any', false, false, false, 355)) {
                // line 356
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'row');
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 360
        if ((!twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'methodRendered', [], 'any', false, false, false, 360) && Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null)))) {
            // line 361
            twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'setMethodRendered', [], 'method', false, false, false, 361);
            // line 362
            $context['method'] = twig_upper_filter($this->env, ($context['method'] ?? null));
            // line 363
            if (twig_in_filter(($context['method'] ?? null), [0 => 'GET', 1 => 'POST'])) {
                // line 364
                $context['form_method'] = ($context['method'] ?? null);
            } else {
                // line 366
                $context['form_method'] = 'POST';
            }
            // line 369
            if ((($context['form_method'] ?? null) != ($context['method'] ?? null))) {
                // line 370
                echo '<input type="hidden" name="_method" value="';
                echo twig_escape_filter($this->env, ($context['method'] ?? null), 'html', null, true);
                echo '" />';
            }
        }
    }

    // line 377
    public function block_form_rows($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 378
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
        foreach ($context['_seq'] as $context['_key'] => $context['child']) {
            // line 379
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'row');
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    // line 383
    public function block_widget_attributes($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 384
        echo 'id="';
        echo twig_escape_filter($this->env, ($context['id'] ?? null), 'html', null, true);
        echo '" name="';
        echo twig_escape_filter($this->env, ($context['full_name'] ?? null), 'html', null, true);
        echo '"';
        // line 385
        if (($context['disabled'] ?? null)) {
            echo ' disabled="disabled"';
        }
        // line 386
        if (($context['required'] ?? null)) {
            echo ' required="required"';
        }
        // line 387
        $this->displayBlock('attributes', $context, $blocks);
    }

    // line 390
    public function block_widget_container_attributes($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 391
        if (!twig_test_empty(($context['id'] ?? null))) {
            echo 'id="';
            echo twig_escape_filter($this->env, ($context['id'] ?? null), 'html', null, true);
            echo '"';
        }
        // line 392
        $this->displayBlock('attributes', $context, $blocks);
    }

    // line 395
    public function block_button_attributes($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 396
        echo 'id="';
        echo twig_escape_filter($this->env, ($context['id'] ?? null), 'html', null, true);
        echo '" name="';
        echo twig_escape_filter($this->env, ($context['full_name'] ?? null), 'html', null, true);
        echo '"';
        if (($context['disabled'] ?? null)) {
            echo ' disabled="disabled"';
        }
        // line 397
        $this->displayBlock('attributes', $context, $blocks);
    }

    // line 400
    public function block_attributes($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 401
        $context['_parent'] = $context;
        $context['_seq']    = twig_ensure_traversable(($context['attr'] ?? null));
        foreach ($context['_seq'] as $context['attrname'] => $context['attrvalue']) {
            // line 402
            echo ' ';
            // line 403
            if (twig_in_filter($context['attrname'], [0 => 'placeholder', 1 => 'title'])) {
                // line 404
                echo twig_escape_filter($this->env, $context['attrname'], 'html', null, true);
                echo '="';
                echo twig_escape_filter($this->env, (((($context['translation_domain'] ?? null) === false)) ? ($context['attrvalue']) : ($this->extensions['Symfony\Bridge\Twig\Extension\TranslationExtension']->trans($context['attrvalue'], [], ($context['translation_domain'] ?? null)))), 'html', null, true);
                echo '"';
            } elseif ((            // line 405
true === $context['attrvalue'])) {
                // line 406
                echo twig_escape_filter($this->env, $context['attrname'], 'html', null, true);
                echo '="';
                echo twig_escape_filter($this->env, $context['attrname'], 'html', null, true);
                echo '"';
            } elseif (!(            // line 407
false === $context['attrvalue'])) {
                // line 408
                echo twig_escape_filter($this->env, $context['attrname'], 'html', null, true);
                echo '="';
                echo twig_escape_filter($this->env, $context['attrvalue'], 'html', null, true);
                echo '"';
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['attrname'], $context['attrvalue'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return 'form_div_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [1178 => 408,  1176 => 407,  1171 => 406,  1169 => 405,  1164 => 404,  1162 => 403,  1160 => 402,  1156 => 401,  1152 => 400,  1148 => 397,  1139 => 396,  1135 => 395,  1131 => 392,  1125 => 391,  1121 => 390,  1117 => 387,  1113 => 386,  1109 => 385,  1103 => 384,  1099 => 383,  1091 => 379,  1087 => 378,  1083 => 377,  1075 => 370,  1073 => 369,  1070 => 366,  1067 => 364,  1065 => 363,  1063 => 362,  1061 => 361,  1059 => 360,  1052 => 356,  1050 => 355,  1046 => 354,  1042 => 353,  1037 => 349,  1029 => 347,  1025 => 346,  1023 => 345,  1021 => 344,  1017 => 343,  1013 => 340,  1010 => 338,  1008 => 337,  1004 => 336,  997 => 332,  995 => 331,  964 => 330,  961 => 328,  958 => 326,  956 => 325,  954 => 324,  952 => 323,  948 => 322,  944 => 319,  942 => 318,  940 => 317,  936 => 316,  932 => 311,  928 => 310,  924 => 307,  922 => 306,  920 => 305,  916 => 304,  912 => 301,  910 => 300,  908 => 299,  906 => 298,  904 => 297,  900 => 296,  896 => 293,  892 => 288,  886 => 284,  879 => 280,  876 => 278,  873 => 276,  871 => 275,  856 => 274,  852 => 271,  849 => 268,  848 => 267,  847 => 266,  845 => 265,  843 => 264,  840 => 262,  838 => 261,  835 => 259,  833 => 258,  831 => 257,  827 => 256,  823 => 251,  821 => 250,  817 => 249,  813 => 246,  811 => 245,  807 => 244,  803 => 241,  801 => 240,  797 => 239,  793 => 236,  791 => 235,  787 => 234,  777 => 231,  773 => 228,  770 => 226,  768 => 225,  766 => 223,  765 => 222,  764 => 221,  762 => 220,  760 => 219,  756 => 218,  752 => 215,  750 => 214,  746 => 213,  742 => 210,  740 => 209,  736 => 208,  732 => 205,  730 => 204,  726 => 203,  722 => 200,  720 => 199,  716 => 198,  711 => 195,  709 => 194,  705 => 193,  701 => 190,  699 => 189,  695 => 188,  691 => 185,  689 => 184,  685 => 183,  681 => 180,  677 => 179,  673 => 176,  671 => 175,  667 => 174,  663 => 171,  661 => 170,  657 => 168,  652 => 164,  648 => 163,  644 => 160,  638 => 159,  632 => 158,  626 => 157,  620 => 156,  614 => 155,  608 => 154,  602 => 153,  597 => 149,  591 => 148,  585 => 147,  579 => 146,  573 => 145,  567 => 144,  561 => 143,  555 => 142,  549 => 139,  547 => 138,  543 => 137,  540 => 135,  538 => 134,  534 => 133,  529 => 129,  519 => 128,  514 => 127,  512 => 126,  509 => 124,  507 => 123,  503 => 122,  498 => 118,  496 => 116,  495 => 115,  494 => 114,  493 => 113,  489 => 112,  486 => 110,  484 => 109,  480 => 108,  475 => 104,  473 => 103,  471 => 102,  469 => 101,  467 => 100,  463 => 99,  460 => 97,  458 => 96,  454 => 95,  440 => 92,  436 => 91,  422 => 88,  418 => 87,  382 => 82,  379 => 80,  377 => 79,  375 => 78,  370 => 77,  368 => 76,  351 => 75,  347 => 74,  343 => 71,  341 => 70,  339 => 69,  333 => 66,  331 => 65,  329 => 64,  327 => 63,  325 => 62,  316 => 60,  314 => 59,  307 => 58,  304 => 56,  302 => 55,  298 => 54,  294 => 51,  288 => 49,  286 => 48,  282 => 47,  278 => 46,  274 => 45,  269 => 41,  266 => 39,  264 => 38,  260 => 37,  252 => 34,  248 => 33,  244 => 30,  241 => 28,  239 => 27,  235 => 26,  231 => 23,  229 => 22,  227 => 21,  224 => 19,  222 => 18,  218 => 17,  214 => 16,  200 => 13,  198 => 12,  194 => 11,  189 => 7,  186 => 5,  184 => 4,  180 => 3,  176 => 400,  174 => 395,  172 => 390,  170 => 383,  168 => 377,  165 => 374,  163 => 353,  161 => 343,  159 => 336,  157 => 322,  155 => 316,  153 => 310,  151 => 304,  149 => 296,  147 => 288,  145 => 284,  143 => 256,  141 => 249,  139 => 244,  137 => 239,  135 => 234,  133 => 218,  131 => 213,  129 => 208,  127 => 203,  125 => 198,  123 => 193,  121 => 188,  119 => 183,  117 => 179,  115 => 174,  113 => 168,  111 => 133,  109 => 122,  107 => 108,  105 => 95,  103 => 91,  101 => 87,  99 => 74,  97 => 54,  95 => 45,  93 => 37,  91 => 33,  89 => 26,  87 => 16,  85 => 11,  83 => 3];
    }

    public function getSourceContext()
    {
        return new Source('', 'form_div_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/form_div_layout.html.twig');
    }
}
