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

/* @Framework/Form/form_errors.html.php */
class __TwigTemplate_361dbbcdb3d2b2b1741123a24657f0bc4697a1bec1cc8f244262d94b87cd6d55 extends \Twig\Template
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
        echo '<?php if (count($errors) > 0): ?>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo $view->escape($error->getMessage()) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif ?>
';
    }

    public function getTemplateName()
    {
        return '@Framework/Form/form_errors.html.php';
    }

    public function getDebugInfo()
    {
        return [37 => 1];
    }

    public function getSourceContext()
    {
        return new Source('', '@Framework/Form/form_errors.html.php', '/Users/mike.shaw/sites/mautic/vendor/symfony/framework-bundle/Resources/views/Form/form_errors.html.php');
    }
}
