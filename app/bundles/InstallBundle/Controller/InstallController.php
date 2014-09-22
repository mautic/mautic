<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

namespace Mautic\InstallBundle\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * InstallController.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class InstallController extends CommonController
{
    /**
     * @return Response A Response instance
     */
    public function stepAction($index = 0)
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $action = $this->generateUrl('mautic_installer_step', array('index' => $index));
        $step   = $configurator->getStep($index);
        $form   = $this->container->get('form.factory')->create($step->getFormType(), $step, array('action' => $action));

        $request = $this->container->get('request');
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $configurator->mergeParameters($step->update($form->getData()));

                try {
                    $configurator->write();
                } catch (RuntimeException $exception) {
                    // TODO - Need to enqueue a message
                    return new RedirectResponse($this->container->get('router')->generate('mautic_installer_step', array('index' => $index)));
                }

                $index++;

                if ($index < $configurator->getStepCount()) {
                    return new RedirectResponse($this->container->get('router')->generate('mautic_installer_step', array('index' => $index)));
                }

                // Before moving to the "final" step, let's build the database out
                $this->clearCache();

                $entityManager = $this->factory->getEntityManager();
                $metadatas     = $entityManager->getMetadataFactory()->getAllMetadata();

                if (!empty($metadatas)) {
                    try {
                        $schemaTool = new SchemaTool($entityManager);
                        $schemaTool->createSchema($metadatas);
                    } catch (ToolsException $exception) {
                        // If the exception concerns the tables already having been created, notify the user of such
                        // TODO - This really should just catch all exceptions to allow the app to handle error display
                        if (strpos($exception->getMessage(), 'Base table or view already exists') !== false) {
                            // TODO - Need to enqueue a message
                        }
                    }
                } else {
                    // TODO - Need to enqueue a message
                }

                return new RedirectResponse($this->container->get('router')->generate('mautic_installer_final'));
            }
        }

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        // Always pass the requirements into the templates
        $majors = $configurator->getRequirements();
        $minors = $configurator->getOptionalSettings();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'    => $form->createView(),
                'index'   => $index,
                'count'   => $configurator->getStepCount(),
                'version' => $this->getVersion(),
                'tmpl'    => $tmpl,
                'majors'  => $majors,
                'minors'  => $minors,
                'appRoot' => $this->container->getParameter('kernel.root_dir'),
            ),
            'contentTemplate' => $step->getTemplate(),
            'passthroughVars' => array(
                'activeLink'     => '#mautic_install_index',
                'mauticContent'  => 'install',
                'route'          => $this->generateUrl('mautic_installer_step', array('index' => $index)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    public function finalAction()
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->container->get('mautic.configurator');

        $welcomeUrl = $this->container->get('router')->generate('mautic_core_index');

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'welcome_url' => $welcomeUrl,
                'parameters'  => $configurator->render(),
                'config_path' => $this->container->getParameter('kernel.root_dir') . '/config/local.php',
                'is_writable' => $configurator->isFileWritable(),
                'version'     => $this->getVersion(),
                'tmpl'        => $tmpl,
            ),
            'contentTemplate' => 'MauticInstallBundle:Install:final.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_install_index',
                'mauticContent'  => 'install',
                'route'          => $this->generateUrl('mautic_installer_step', array('index' => 0)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    protected function getVersion()
    {
        $kernel = $this->container->get('kernel');

        return $kernel::VERSION;
    }
}
