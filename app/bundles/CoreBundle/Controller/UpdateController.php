<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class UpdateController.
 */
class UpdateController extends CommonController
{
    /**
     * Generates the update view.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->factory->getHelper('update');
        $updateData   = $updateHelper->fetchData();

        return $this->delegateView([
            'viewParameters' => [
                'updateData'     => $updateData,
                'currentVersion' => $this->factory->getVersion(),
            ],
            'contentTemplate' => 'MauticCoreBundle:Update:index.html.php',
            'passthroughVars' => [
                'mauticContent' => 'update',
                'route'         => $this->generateUrl('mautic_core_update'),
            ],
        ]);
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function schemaAction()
    {
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $result       = 0;
        $failed       = false;
        $noMigrations = true;
        $iterator     = new \FilesystemIterator($this->container->getParameter('kernel.root_dir').'/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
            $env  = $this->factory->getEnvironment();
            $args = ['console', 'doctrine:migrations:migrate', '--no-interaction', '--env='.$env];

            if ($env == 'prod') {
                $args[] = '--no-debug';
            }

            $input       = new ArgvInput($args);
            $application = new Application($this->get('kernel'));
            $application->setAutoExit(false);
            $output = new BufferedOutput();

            $minExecutionTime = 300;
            $maxExecutionTime = (int) ini_get('max_execution_time');
            if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
                ini_set('max_execution_time', $minExecutionTime);
            }

            $result = $application->run($input, $output);

            $outputBuffer = $output->fetch();

            // Check if migrations executed
            $noMigrations = ($result === 0 && strpos($outputBuffer, 'No migrations') !== false);
        }

        if ($result !== 0) {
            // Log the output
            $outputBuffer = trim(preg_replace('/\n\s*\n/s', ' \\ ', $outputBuffer));
            $outputBuffer = preg_replace('/\s\s+/', ' ', trim($outputBuffer));
            $this->factory->getLogger()->log('error', '[UPGRADE ERROR] Exit code '.$result.'; '.$outputBuffer);

            $failed = true;
        } elseif ($this->request->get('update', 0)) {
            // This was a retry from the update so call up the finalizeAction to finish the process
            $this->forward('MauticCoreBundle:Ajax:updateFinalization',
                [
                    'request' => $this->request,
                ]
            );
        }

        return $this->delegateView([
            'viewParameters' => [
                'failed'       => $failed,
                'noMigrations' => $noMigrations,
            ],
            'contentTemplate' => 'MauticCoreBundle:Update:schema.html.php',
            'passthroughVars' => [
                'mauticContent' => 'update',
                'route'         => $this->generateUrl('mautic_core_update_schema'),
            ],
        ]);
    }
}
