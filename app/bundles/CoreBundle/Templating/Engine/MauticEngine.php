<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Engine;

use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine;

class MauticEngine extends PhpEngine
{
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function supports($name)
    {
        $template = $this->parser->parse($name);

        return 'php' === $template->get('engine');
    }


    /**
     * {@inheritdoc}
     * Overriding parent in order to populate Mautic blocks with _content
     *
     * @throws \InvalidArgumentException if the template does not exist
     *
     * @api
     */
    public function render($name, array $parameters = array())
    {
        $storage = $this->load($name);
        $key = hash('sha256', serialize($storage));
        $this->current = $key;
        $this->parents[$key] = null;

        // attach the global variables
        $parameters = array_replace($this->getGlobals(), $parameters);
        // render
        if (false === $content = $this->evaluate($storage, $parameters)) {
            throw new \RuntimeException(sprintf('The template "%s" cannot be rendered.', $this->parser->parse($name)));
        }

        // decorator
        if ($this->parents[$key]) {
            $blocks        = $this->get('blocks');
            $this->stack[] = $blocks->get('_content');
            $blocks->set('_content', $content);

            $content = $this->render($this->parents[$key], $parameters);

            $blocks->set('_content', array_pop($this->stack));
        }

        return $content;
    }

}