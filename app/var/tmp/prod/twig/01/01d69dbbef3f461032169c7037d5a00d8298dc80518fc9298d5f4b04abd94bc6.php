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

/* @Framework/Form/form_label.html.php */
class __TwigTemplate_c9c6f84364a360146567c79beb17a7d9a70a7010dd4de6a9744f37ba42d28b05 extends \Twig\Template
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
        echo "<?php if (false !== \$label): ?>
<?php if (\$required) { \$label_attr['class'] = trim((isset(\$label_attr['class']) ? \$label_attr['class'] : '').' required'); } ?>
<?php if (!\$compound) { \$label_attr['for'] = \$id; } ?>
<?php if (!\$label) { \$label = isset(\$label_format)
    ? strtr(\$label_format, ['%name%' => \$name, '%id%' => \$id])
    : \$view['form']->humanize(\$name); } ?>
<label<?php if (\$label_attr) { echo ' '.\$view['form']->block(\$form, 'attributes', ['attr' => \$label_attr]); } ?>><?php echo \$view->escape(false !== \$translation_domain ? \$view['translator']->trans(\$label, [], \$translation_domain) : \$label) ?></label>
<?php endif ?>
";
    }

    public function getTemplateName()
    {
        return '@Framework/Form/form_label.html.php';
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', '@Framework/Form/form_label.html.php', '/Users/mike.shaw/sites/mautic/vendor/symfony/framework-bundle/Resources/views/Form/form_label.html.php');
    }
}
