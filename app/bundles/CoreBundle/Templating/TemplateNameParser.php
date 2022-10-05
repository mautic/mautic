<?php

namespace Mautic\CoreBundle\Templating;

use Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser as BaseTemplateNameParser;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Class TemplateNameParser.
 */
class TemplateNameParser extends BaseTemplateNameParser
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;
    private \Mautic\CoreBundle\Helper\ThemeHelper $themeHelper;
    private \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper;

    /**
     * {@inheritdoc}
     */
    public function __construct(KernelInterface $kernel, \Mautic\CoreBundle\Helper\ThemeHelper $themeHelper, \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper)
    {
        parent::__construct($kernel);

        $this->container = $kernel->getContainer();
        $this->themeHelper = $themeHelper;
        $this->pathsHelper = $pathsHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function parse($name)
    {
        if ($name instanceof TemplateReferenceInterface) {
            return $name;
        } elseif (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // normalize name
        $name = str_replace(':/', ':', preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')));

        if (false !== strpos($name, '..')) {
            throw new \RuntimeException(sprintf('Template name "%s" contains invalid characters.', $name));
        }

        if (!preg_match('/^([^:]*):([^:]*):(.+)\.([^\.]+)\.([^\.]+)$/', $name, $matches)) {
            $templateReference = parent::parse($name);

            if ($templateReference->get('engine')) {
                return $templateReference;
            }

            throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid (format is "bundle:section:template.format.engine").', $name));
        }

        //check for template specific theme override
        $bundle = $matches[1];
        preg_match('/^(.*?)\|(.*?)$/', $matches[1], $templateOverride);
        if (!empty($templateOverride[1])) {
            $themeOverride = $templateOverride[1];
            $bundle        = $templateOverride[2];
        }

        $template = new TemplateReference($bundle, $matches[2], $matches[3], $matches[4], $matches[5]);

        if (!empty($themeOverride)) {
            $template->setThemeOverride($themeOverride);
        }

        $template->setThemeHelper($this->themeHelper);
        $template->setPathsHelper($this->pathsHelper);

        if ($template->get('bundle')) {
            try {
                $this->kernel->getBundle($template->get('bundle'));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(sprintf('Template name "%s" is not valid.', $name), 0, $e);
            }
        }

        return $this->cache[$name] = $template;
    }
}
