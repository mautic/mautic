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

/* @Framework/Form/date_widget.html.php */
class __TwigTemplate_6ca2891112bd00b93c60c35084bacdd0ea26da2a3943fab1d94af92c6dca362e extends \Twig\Template
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
        echo "<?php if (\$widget == 'single_text'): ?>
    <?php echo \$view['form']->block(\$form, 'form_widget_simple'); ?>
<?php else: ?>
    <div <?php echo \$view['form']->block(\$form, 'widget_container_attributes') ?>>
        <?php echo str_replace(['";
        // line 5
        echo twig_escape_filter($this->env, ($context['year'] ?? null), 'html', null, true);
        echo "', '";
        echo twig_escape_filter($this->env, ($context['month'] ?? null), 'html', null, true);
        echo "', '";
        echo twig_escape_filter($this->env, ($context['day'] ?? null), 'html', null, true);
        echo "'], [
            \$view['form']->widget(\$form['year']),
            \$view['form']->widget(\$form['month']),
            \$view['form']->widget(\$form['day']),
        ], \$date_pattern) ?>
    </div>
<?php endif ?>
";
    }

    public function getTemplateName()
    {
        return '@Framework/Form/date_widget.html.php';
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [43 => 5,  37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', '@Framework/Form/date_widget.html.php', '/Users/mike.shaw/sites/mautic/vendor/symfony/framework-bundle/Resources/views/Form/date_widget.html.php');
    }
}
