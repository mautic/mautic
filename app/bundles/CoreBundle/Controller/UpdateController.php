<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\UpdateHelper;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class UpdateController extends CommonController
{
    /**
     * Generates the update view.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(UpdateHelper $updateHelper)
    {
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $updateData           = $updateHelper->fetchData();
        $coreParametersHelper = $this->coreParametersHelper;

        return $this->delegateView([
            'viewParameters' => [
                'updateData'        => $updateData,
                'currentVersion'    => MAUTIC_VERSION,
                'isComposerEnabled' => $coreParametersHelper->get('composer_updates', false),
            ],
            'contentTemplate' => '@MauticCore/Update/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'update',
                'route'         => $this->generateUrl('mautic_core_update'),
            ],
        ]);
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function schemaAction(Request $request, LoggerInterface $mauticLogger, KernelInterface $kernel)
    {
        if (!$this->user->isAdmin()) {
            return $this->accessDenied();
        }

        $result       = 0;
        $failed       = false;
        $noMigrations = true;
        $iterator     = new \FilesystemIterator($this->getParameter('mautic.application_dir').'/app/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
            $args = ['console', 'doctrine:migrations:migrate', '--no-interaction', '--env='.MAUTIC_ENV];

            if ('prod' === MAUTIC_ENV) {
                $args[] = '--no-debug';
            }

            $input       = new ArgvInput($args);
            $application = new Application($kernel);
            $application->setAutoExit(false);
            $output = new BufferedOutput();

            $minExecutionTime = 300;
            $maxExecutionTime = (int) ini_get('max_execution_time');
            if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
                ini_set('max_execution_time', "$minExecutionTime");
            }

            $result = $application->run($input, $output);

            $outputBuffer = $output->fetch();

            // Check if migrations executed
            $noMigrations = (0 === $result && str_contains($outputBuffer, 'No migrations'));
        }

        if (0 !== $result) {
            // Log the output
            $outputBuffer = trim(preg_replace('/\n\s*\n/s', ' \\ ', $outputBuffer));
            $outputBuffer = preg_replace('/\s\s+/', ' ', trim($outputBuffer));
            $mauticLogger->log('error', '[UPGRADE ERROR] Exit code '.$result.'; '.$outputBuffer);

            $failed = true;
        } elseif ($request->get('update', 0)) {
            // This was a retry from the update so call up the finalizeAction to finish the process
            $this->forward('Mautic\CoreBundle\Controller\AjaxController::updateFinalizationAction');
        }

        return $this->delegateView([
            'viewParameters' => [
                'failed'       => $failed,
                'noMigrations' => $noMigrations,
            ],
            'contentTemplate' => '@MauticCore/Update/schema.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'update',
                'route'         => $this->generateUrl('mautic_core_update_schema'),
            ],
        ]);
    }
}
