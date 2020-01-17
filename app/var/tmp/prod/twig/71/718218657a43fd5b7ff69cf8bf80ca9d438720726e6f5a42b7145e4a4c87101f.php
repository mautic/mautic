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

/* form_table_layout.html.twig */
class __TwigTemplate_6904a85219cd8014831b0e3d7b9fbbf5d7b3e786721559c02284316befdbc1f1 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        // line 1
        $_trait_0 = $this->loadTemplate('form_div_layout.html.twig', 'form_table_layout.html.twig', 1);
        if (!$_trait_0->isTraitable()) {
            throw new RuntimeError('Template "'.'form_div_layout.html.twig'.'" cannot be used as a trait.', 1, $this->source);
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            [
                'form_row'             => [$this, 'block_form_row'],
                'button_row'           => [$this, 'block_button_row'],
                'hidden_row'           => [$this, 'block_hidden_row'],
                'form_widget_compound' => [$this, 'block_form_widget_compound'],
            ]
        );
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        $this->displayBlock('form_row', $context, $blocks);
        // line 15
        $this->displayBlock('button_row', $context, $blocks);
        // line 24
        $this->displayBlock('hidden_row', $context, $blocks);
        // line 32
        $this->displayBlock('form_widget_compound', $context, $blocks);
    }

    // line 3
    public function block_form_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo '<tr>
        <td>';
        // line 6
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'label');
        // line 7
        echo '</td>
        <td>';
        // line 9
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
        // line 10
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 11
        echo '</td>
    </tr>';
    }

    // line 15
    public function block_button_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 16
        echo '<tr>
        <td></td>
        <td>';
        // line 19
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 20
        echo '</td>
    </tr>';
    }

    // line 24
    public function block_hidden_row($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 25
        echo '<tr style="display: none">
        <td colspan="2">';
        // line 27
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'widget');
        // line 28
        echo '</td>
    </tr>';
    }

    // line 32
    public function block_form_widget_compound($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 33
        echo '<table ';
        $this->displayBlock('widget_container_attributes', $context, $blocks);
        echo '>';
        // line 34
        if ((Symfony\Bridge\Twig\Extension\twig_is_root_form(($context['form'] ?? null)) && (twig_length_filter($this->env, ($context['errors'] ?? null)) > 0))) {
            // line 35
            echo '<tr>
            <td colspan="2">';
            // line 37
            echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'errors');
            // line 38
            echo '</td>
        </tr>';
        }
        // line 41
        $this->displayBlock('form_rows', $context, $blocks);
        // line 42
        echo $this->env->getRuntime('Symfony\Component\Form\FormRenderer')->searchAndRenderBlock(($context['form'] ?? null), 'rest');
        // line 43
        echo '</table>';
    }

    public function getTemplateName()
    {
        return 'form_table_layout.html.twig';
    }

    public function getDebugInfo()
    {
        return [136 => 43,  134 => 42,  132 => 41,  128 => 38,  126 => 37,  123 => 35,  121 => 34,  117 => 33,  113 => 32,  108 => 28,  106 => 27,  103 => 25,  99 => 24,  94 => 20,  92 => 19,  88 => 16,  84 => 15,  79 => 11,  77 => 10,  75 => 9,  72 => 7,  70 => 6,  67 => 4,  63 => 3,  59 => 32,  57 => 24,  55 => 15,  53 => 3,  30 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', 'form_table_layout.html.twig', '/Users/mike.shaw/sites/mautic/vendor/symfony/twig-bridge/Resources/views/Form/form_table_layout.html.twig');
    }
}
