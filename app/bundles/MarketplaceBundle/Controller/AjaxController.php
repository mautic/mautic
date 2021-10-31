<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\MarketplaceBundle\Service\Composer;
use Mautic\PluginBundle\Facade\ReloadFacade;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AjaxController extends CommonAjaxController
{
    public function installPackageAction(Request $request): JsonResponse
    {
        /** @var TranslatorInterface */
        $translator = $this->get('translator');
        /** @var Composer */
        $composer = $this->get('marketplace.service.composer');
        /** @var KernelInterface */
        $kernel = $this->get('kernel');
        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $translator->trans('test') || 'ERROR',
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if ($composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => 'TODO already installed',
            ], 400);
        }

        // TODO error handling
        $composer->install($packageName);

        // Clear cache
        $env = $kernel->getEnvironment();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
            '--env'   => $env,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        //$content = $output->fetch();

        /** @var ReloadFacade */
        $reloadFacade = $this->get('mautic.plugin.facade.reload');
        $reloadFacade->reloadPlugins();

        // This works, now handle logic here
        return $this->sendJsonResponse([
            'success' => true,
        ]);
    }

    public function removePackageAction(Request $request): JsonResponse
    {
        /** @var TranslatorInterface */
        $translator = $this->get('translator');
        /** @var Composer */
        $composer = $this->get('marketplace.service.composer');
        /** @var KernelInterface */
        $kernel = $this->get('kernel');
        $data   = json_decode($request->getContent(), true);

        if (empty($data['vendor']) || empty($data['package'])) {
            return $this->sendJsonResponse([
                'error' => $translator->trans('test') || 'ERROR',
            ], 400);
        }

        $packageName = $data['vendor'].'/'.$data['package'];

        if (!$composer->isInstalled($packageName)) {
            return $this->sendJsonResponse([
                'error' => 'TODO plugin not installed, cant remove',
            ], 400);
        }

        // TODO error handling
        $composerResult = $composer->remove($packageName);

        if (0 !== $composerResult->exitCode) {
            return $this->sendJsonResponse([
                'error' => 'Error while removing package using Composer:'.$composerResult->output,
            ], 500);
        }

        // Clear cache
        $env = $kernel->getEnvironment();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
            '--env'   => $env,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        //$content = $output->fetch();

        /** @var ReloadFacade */
        $reloadFacade = $this->get('mautic.plugin.facade.reload');
        $reloadFacade->reloadPlugins();

        // This works, now handle logic here
        return $this->sendJsonResponse([
            'success' => true,
        ]);
    }
}
