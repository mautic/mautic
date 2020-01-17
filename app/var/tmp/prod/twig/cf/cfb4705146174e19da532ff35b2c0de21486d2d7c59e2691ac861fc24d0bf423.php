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

/* bootstrap_base_layout.html.twig */
class __TwigTemplate_8516816a299acc7f61f9b8b829a9670e9711bd446107596bf0680e815156c7cb extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('form_div_layout.html.twig', 'bootstrap_base_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'form_div_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'textarea_widget'         => [$this, 'block_textarea_widget'],
                'money_widget'            => [$this, 'block_money_widget'],
                'percent_widget'          => [$this, 'block_percent_widget'],
                'datetime_widget'         => [$this, 'block_datetime_widget'],
                'date_widget'             => [$this, 'block_date_widget'],
                'time_widget'             => [$this, 'block_time_widget'],
                'dateinterval_widget'     => [$this, 'block_dateinterval_widget'],
                'choice_widget_collapsed' => [$this, 'block_choice_widget_collapsed'],
                'choice_widget_expanded'  => [$this, 'block_choice_widget_expanded'],
                'choice_label'            => [$this, 'block_choice_label'],
                'checkbox_label'          => [$this, 'block_checkbox_label'],
                'radio_label'             => [$this, 'block_radio_label'],
                'button_row'              => [$this, 'block_button_row'],
                'choice_row'              => [$this, 'block_choice_row'],
                'date_row'                => [$this, 'block_date_row'],
                'time_row'                => [$this, 'block_time_row'],
                'datetime_row'            => [$this, 'block_datetime_row'],
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
        $this->displayBlock('textarea_widget', $context, $blocks);
        // line 9
        echo '
';
        // line 10
        $this->displayBlock('money_widget', $context, $blocks);
        // line 27
        echo '
';
        // line 28
        $this->displayBlock('percent_widget', $context, $blocks);
        // line 34
        echo '
';
        // line 35
        $this->displayBlock('datetime_widget', $context, $blocks);
        // line 58
        echo '
';
        // line 59
        $this->displayBlock('date_widget', $context, $blocks);
        // line 83
        echo '
';
        // line 84
        $this->displayBlock('time_widget', $context, $blocks);
        // line 102
        $this->displayBlock('dateinterval_widget', $context, $blocks);
        // line 140
        $this->displayBlock('choice_widget_collapsed', $context, $blocks);
        // line 144
        echo '
';
        // line 145
        $this->displayBlock('choice_widget_expanded', $context, $blocks);
        // line 164
        echo '
';
        // line 166
        echo '
';
        // line 167
        $this->displayBlock('choice_label', $context, $blocks);
        // line 172
        echo '
';
        // line 173
        $this->displayBlock('checkbox_label', $context, $blocks);
        // line 176
        echo '
';
        // line 177
        $this->displayBlock('radio_label', $context, $blocks);
        // line 180
        echo '
';
        // line 182
        echo '
';
        // line 183
        $this->displayBlock('button_row', $context, $blocks);
        // line 188
        echo '
';
        // line 189
        $this->displayBlock('choice_row', $context, $blocks);
        // line 193
        echo '
';
        // line 194
        $this->displayBlock('date_row', $context, $blocks);
        // line 198
        echo '
';
        // line 199
        $this->displayBlock('time_row', $context, $blocks);
        // line 203
        echo '
';
        // line 204
        $this->displayBlock('datetime_row', $context, $blocks);
    }

    // line 5
    public function block_textarea_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 6
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 6)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 6), '')) : ('')).' form-control'))]);
        // line 7
        $this->displayParentBlock('textarea_widget', $context, $blocks);
    }

    // line 10
    public function block_money_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 11
        $context['prepend'] =  !(is_string($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = ($context['money_pattern'] ?? null)) && is_string($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = '{{') && ('' === $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 || 0 === strpos($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4, $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144)));
        // line 12
        echo '    ';
        $context['append'] =  !(is_string($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = ($context['money_pattern'] ?? null)) && is_string($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = '}}') && ('' === $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 === substr($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b, -strlen($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002))));
        // line 13
        echo '    ';
        if ((($context['prepend'] ?? null) || ($context['append'] ?? null))) {
            // line 14
            echo '        <div class="input-group';
            echo twig_escape_filter($this->env, (((isset($context['group_class']) || array_key_exists('group_class', $context))) ? (_twig_default_filter(($context['group_class'] ?? null), '')) : ('')), 'html', null, true);
            echo '">
            ';
            // line 15
            if (($context['prepend'] ?? null)) {
                // line 16
                echo '                <span class="input-group-addon">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
            ';
            }
            // line 18
            $this->displayBlock('form_widget_simple', $context, $blocks);
            // line 19
            if (($context['append'] ?? null)) {
                // line 20
                echo '                <span class="input-group-addon">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->encodeCurrency($this->env, ($context['money_pattern'] ?? null));
                echo '</span>
            ';
            }
            // line 22
            echo '        </div>
    ';
        } else {
            // line 24
            $this->displayBlock('form_widget_simple', $context, $blocks);
        }
    }

    // line 28
    public function block_percent_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 29
        echo '<div class="input-group">';
        // line 30
        $this->displayBlock('form_widget_simple', $context, $blocks);
        // line 31
        echo '<span class="input-group-addon">%</span>
    </div>';
    }

    // line 35
    public function block_datetime_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 36
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 37
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 39
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 39)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 39), '')) : ('')).' form-inline'))]);
            // line 40
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 41
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 41), 'errors');
            // line 42
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 42), 'errors');
            // line 44
            echo '<div class="sr-only">';
            // line 45
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, true, false, 45), 'year', [], 'any', true, true, false, 45)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 45), 'year', [], 'any', false, false, false, 45), 'label');
            }
            // line 46
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, true, false, 46), 'month', [], 'any', true, true, false, 46)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 46), 'month', [], 'any', false, false, false, 46), 'label');
            }
            // line 47
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, true, false, 47), 'day', [], 'any', true, true, false, 47)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 47), 'day', [], 'any', false, false, false, 47), 'label');
            }
            // line 48
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, true, false, 48), 'hour', [], 'any', true, true, false, 48)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 48), 'hour', [], 'any', false, false, false, 48), 'label');
            }
            // line 49
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, true, false, 49), 'minute', [], 'any', true, true, false, 49)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 49), 'minute', [], 'any', false, false, false, 49), 'label');
            }
            // line 50
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, true, false, 50), 'second', [], 'any', true, true, false, 50)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 50), 'second', [], 'any', false, false, false, 50), 'label');
            }
            // line 51
            echo '</div>';
            // line 53
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'date', [], 'any', false, false, false, 53), 'widget', ['datetime' => true]);
            // line 54
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'time', [], 'any', false, false, false, 54), 'widget', ['datetime' => true]);
            // line 55
            echo '</div>';
        }
    }

    // line 59
    public function block_date_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 60
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 61
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 63
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 63)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 63), '')) : ('')).' form-inline'))]);
            // line 64
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || !($context['datetime'] ?? null))) {
                // line 65
                echo '<div ';
                $this->displayBlock('widget_container_attributes', $context, $blocks);
                echo '>';
            }
            // line 67
            echo '            <div class="sr-only">
                ';
            // line 68
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'year', [], 'any', false, false, false, 68), 'label');
            echo '
                ';
            // line 69
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'month', [], 'any', false, false, false, 69), 'label');
            echo '
                ';
            // line 70
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'day', [], 'any', false, false, false, 70), 'label');
            echo '
            </div>';
            // line 73
            echo twig_replace_filter(($context['date_pattern'] ?? null), ['{{ year }}' => // line 74
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'year', [], 'any', false, false, false, 74), 'widget'), '{{ month }}' =>             // line 75
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'month', [], 'any', false, false, false, 75), 'widget'), '{{ day }}' =>             // line 76
$this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'day', [], 'any', false, false, false, 76), 'widget'), ]);
            // line 78
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || !($context['datetime'] ?? null))) {
                // line 79
                echo '</div>';
            }
        }
    }

    // line 84
    public function block_time_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 85
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 86
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 88
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 88)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 88), '')) : ('')).' form-inline'))]);
            // line 89
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || (false == ($context['datetime'] ?? null)))) {
                // line 90
                echo '<div ';
                $this->displayBlock('widget_container_attributes', $context, $blocks);
                echo '>';
            }
            // line 92
            echo '<div class="sr-only">';
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hour', [], 'any', false, false, false, 92), 'label');
            echo '</div>';
            // line 93
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hour', [], 'any', false, false, false, 93), 'widget');
            // line 94
            if (($context['with_minutes'] ?? null)) {
                echo ':<div class="sr-only">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minute', [], 'any', false, false, false, 94), 'label');
                echo '</div>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minute', [], 'any', false, false, false, 94), 'widget');
            }
            // line 95
            if (($context['with_seconds'] ?? null)) {
                echo ':<div class="sr-only">';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'second', [], 'any', false, false, false, 95), 'label');
                echo '</div>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'second', [], 'any', false, false, false, 95), 'widget');
            }
            // line 96
            if ((!(isset($context['datetime']) || array_key_exists('datetime', $context)) || (false == ($context['datetime'] ?? null)))) {
                // line 97
                echo '</div>';
            }
        }
    }

    // line 102
    public function block_dateinterval_widget($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 103
        if ((($context['widget'] ?? null) == 'single_text')) {
            // line 104
            $this->displayBlock('form_widget_simple', $context, $blocks);
        } else {
            // line 106
            $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 106)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 106), '')) : ('')).' form-inline'))]);
            // line 107
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 108
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
            // line 109
            echo '<div class="table-responsive">
                <table class="table ';
            // line 110
            echo twig_escape_filter($this->env, (((isset($context['table_class']) || array_key_exists('table_class', $context))) ? (_twig_default_filter(($context['table_class'] ?? null), 'table-bordered table-condensed table-striped')) : ('table-bordered table-condensed table-striped')), 'html', null, true);
            echo '" role="presentation">
                    <thead>
                    <tr>';
            // line 113
            if (($context['with_years'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 113), 'label');
                echo '</th>';
            }
            // line 114
            if (($context['with_months'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 114), 'label');
                echo '</th>';
            }
            // line 115
            if (($context['with_weeks'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 115), 'label');
                echo '</th>';
            }
            // line 116
            if (($context['with_days'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 116), 'label');
                echo '</th>';
            }
            // line 117
            if (($context['with_hours'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 117), 'label');
                echo '</th>';
            }
            // line 118
            if (($context['with_minutes'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 118), 'label');
                echo '</th>';
            }
            // line 119
            if (($context['with_seconds'] ?? null)) {
                echo '<th>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 119), 'label');
                echo '</th>';
            }
            // line 120
            echo '</tr>
                    </thead>
                    <tbody>
                    <tr>';
            // line 124
            if (($context['with_years'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'years', [], 'any', false, false, false, 124), 'widget');
                echo '</td>';
            }
            // line 125
            if (($context['with_months'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'months', [], 'any', false, false, false, 125), 'widget');
                echo '</td>';
            }
            // line 126
            if (($context['with_weeks'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'weeks', [], 'any', false, false, false, 126), 'widget');
                echo '</td>';
            }
            // line 127
            if (($context['with_days'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'days', [], 'any', false, false, false, 127), 'widget');
                echo '</td>';
            }
            // line 128
            if (($context['with_hours'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'hours', [], 'any', false, false, false, 128), 'widget');
                echo '</td>';
            }
            // line 129
            if (($context['with_minutes'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'minutes', [], 'any', false, false, false, 129), 'widget');
                echo '</td>';
            }
            // line 130
            if (($context['with_seconds'] ?? null)) {
                echo '<td>';
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'seconds', [], 'any', false, false, false, 130), 'widget');
                echo '</td>';
            }
            // line 131
            echo '</tr>
                    </tbody>
                </table>
            </div>';
            // line 135
            if (($context['with_invert'] ?? null)) {
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(twig_get_attribute($this->env, $this->source, ($context['form'] ?? null), 'invert', [], 'any', false, false, false, 135), 'widget');
            }
            // line 136
            echo '</div>';
        }
    }

    // line 140
    public function block_choice_widget_collapsed($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 141
        $context['attr'] = twig_array_merge(($context['attr'] ?? null), ['class' => twig_trim_filter((((twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', true, true, false, 141)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['attr'] ?? null), 'class', [], 'any', false, false, false, 141), '')) : ('')).' form-control'))]);
        // line 142
        $this->displayParentBlock('choice_widget_collapsed', $context, $blocks);
    }

    // line 145
    public function block_choice_widget_expanded($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 146
        if (twig_in_filter('-inline', ((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 146)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 146), '')) : ('')))) {
            // line 147
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['child']) {
                // line 148
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget', ['parent_label_class' => ((twig_get_attribute($this->env, $this->source,                 // line 149
($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 149)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 149), '')) : ('')), 'translation_domain' =>                 // line 150
($context['choice_translation_domain'] ?? null), ]);
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        } else {
            // line 154
            echo '<div ';
            $this->displayBlock('widget_container_attributes', $context, $blocks);
            echo '>';
            // line 155
            $context['_parent'] = $context;
            $context['_seq']    = twig_ensure_traversable(($context['form'] ?? null));
            foreach ($context['_seq'] as $context['_key'] => $context['child']) {
                // line 156
                echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock($context['child'], 'widget', ['parent_label_class' => ((twig_get_attribute($this->env, $this->source,                 // line 157
($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 157)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 157), '')) : ('')), 'translation_domain' =>                 // line 158
($context['choice_translation_domain'] ?? null), ]);
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['child'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 161
            echo '</div>';
        }
    }

    // line 167
    public function block_choice_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 169
        $context['label_attr'] = twig_array_merge(($context['label_attr'] ?? null), ['class' => twig_trim_filter(twig_replace_filter(((twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', true, true, false, 169)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, ($context['label_attr'] ?? null), 'class', [], 'any', false, false, false, 169), '')) : ('')), ['checkbox-inline' => '', 'radio-inline' => '', 'checkbox-custom' => '', 'radio-custom' => '']))]);
        // line 170
        $this->displayBlock('form_label', $context, $blocks);
    }

    // line 173
    public function block_checkbox_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 174
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 177
    public function block_radio_label($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 178
        $this->displayBlock('checkbox_radio_label', $context, $blocks);
    }

    // line 183
    public function block_button_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 184
        echo '<div class="form-group">';
        // line 185
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 186
        echo '</div>';
    }

    // line 189
    public function block_choice_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 190
        $context['force_error'] = true;
        // line 191
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 194
    public function block_date_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 195
        $context['force_error'] = true;
        // line 196
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 199
    public function block_time_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 200
        $context['force_error'] = true;
        // line 201
        $this->displayBlock('form_row', $context, $blocks);
    }

    // line 204
    public function block_datetime_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 205
        $context['force_error'] = true;
        // line 206
        $this->displayBlock('form_row', $context, $blocks);
    }

    public function getTemplateName()
    {
        return 'bootstrap_base_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [623 => 206,  621 => 205,  617 => 204,  613 => 201,  611 => 200,  607 => 199,  603 => 196,  601 => 195,  597 => 194,  593 => 191,  591 => 190,  587 => 189,  583 => 186,  581 => 185,  579 => 184,  575 => 183,  571 => 178,  567 => 177,  563 => 174,  559 => 173,  555 => 170,  553 => 169,  549 => 167,  544 => 161,  538 => 158,  537 => 157,  536 => 156,  532 => 155,  528 => 154,  521 => 150,  520 => 149,  519 => 148,  515 => 147,  513 => 146,  509 => 145,  505 => 142,  503 => 141,  499 => 140,  494 => 136,  490 => 135,  485 => 131,  479 => 130,  473 => 129,  467 => 128,  461 => 127,  455 => 126,  449 => 125,  443 => 124,  438 => 120,  432 => 119,  426 => 118,  420 => 117,  414 => 116,  408 => 115,  402 => 114,  396 => 113,  391 => 110,  388 => 109,  386 => 108,  382 => 107,  380 => 106,  377 => 104,  375 => 103,  371 => 102,  365 => 97,  363 => 96,  356 => 95,  349 => 94,  347 => 93,  343 => 92,  338 => 90,  336 => 89,  334 => 88,  331 => 86,  329 => 85,  325 => 84,  319 => 79,  317 => 78,  315 => 76,  314 => 75,  313 => 74,  312 => 73,  308 => 70,  304 => 69,  300 => 68,  297 => 67,  292 => 65,  290 => 64,  288 => 63,  285 => 61,  283 => 60,  279 => 59,  274 => 55,  272 => 54,  270 => 53,  268 => 51,  264 => 50,  260 => 49,  256 => 48,  252 => 47,  248 => 46,  244 => 45,  242 => 44,  240 => 42,  238 => 41,  234 => 40,  232 => 39,  229 => 37,  227 => 36,  223 => 35,  218 => 31,  216 => 30,  214 => 29,  210 => 28,  205 => 24,  201 => 22,  195 => 20,  193 => 19,  191 => 18,  185 => 16,  183 => 15,  178 => 14,  175 => 13,  172 => 12,  170 => 11,  166 => 10,  162 => 7,  160 => 6,  156 => 5,  152 => 204,  149 => 203,  147 => 199,  144 => 198,  142 => 194,  139 => 193,  137 => 189,  134 => 188,  132 => 183,  129 => 182,  126 => 180,  124 => 177,  121 => 176,  119 => 173,  116 => 172,  114 => 167,  111 => 166,  108 => 164,  106 => 145,  103 => 144,  101 => 140,  99 => 102,  97 => 84,  94 => 83,  92 => 59,  89 => 58,  87 => 35,  84 => 34,  82 => 28,  79 => 27,  77 => 10,  74 => 9,  72 => 5,  69 => 4,  66 => 2,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'bootstrap_base_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/bootstrap_base_layout.html.twig');
    }
}
