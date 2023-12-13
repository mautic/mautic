<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CommandListEvent;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Event\UpgradeEvent;
use Mautic\CoreBundle\Exception\RecordCanNotUnpublishException;
use Mautic\CoreBundle\Factory\IpLookupFactory;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\PreUpdateCheckError;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\CoreBundle\IpLookup\IpLookupFormInterface;
use Mautic\CoreBundle\Model\FormModel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AjaxController extends CommonController
{
    /**
     * @param array $dataArray
     * @param int   $statusCode
     * @param bool  $addIgnoreWdt
     *
     * @throws \Exception
     */
    protected function sendJsonResponse($dataArray, $statusCode = null, $addIgnoreWdt = true): JsonResponse
    {
        $response = new JsonResponse();

        if ('dev' == $this->getParameter('kernel.environment') && $addIgnoreWdt) {
            $dataArray['ignore_wdt'] = 1;
        }

        if (null !== $statusCode) {
            $response->setStatusCode($statusCode);
        }

        $response->setData($dataArray);

        return $response;
    }

    /**
     * Executes an action requested via ajax.
     *
     * @return Response
     */
    public function delegateAjaxAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        // process ajax actions
        $action     = $request->get('action');
        $bundleName = null;
        if (empty($action)) {
            // check POST
            $action = $request->request->get('action');
        }

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if (str_contains($action, ':')) {
                // call the specified bundle's ajax action
                $parts     = explode(':', $action);
                $namespace = 'Mautic';

                if (3 == count($parts) && 'plugin' == $parts['0']) {
                    $namespace = 'MauticPlugin';
                    array_shift($parts);
                }

                if (2 == count($parts)) {
                    $bundleName = $parts[0];
                    $bundle     = ucfirst($bundleName);
                    $action     = $parts[1];

                    if (!$classExists = class_exists($namespace.'\\'.$bundle.'Bundle\\Controller\\AjaxController')) {
                        // Check if a plugin is prefixed with Mautic
                        $bundle      = 'Mautic'.$bundle;
                        $classExists = class_exists($namespace.'\\'.$bundle.'Bundle\\Controller\\AjaxController');
                    }

                    if ($classExists) {
                        return $this->forwardWithPost(
                            $namespace.'\\'.$bundle.'Bundle\\Controller\\AjaxController::executeAjaxAction',
                            $request->request->all(),
                            [
                                'action'  => $action,
                                'bundle'  => $bundleName,
                            ],
                            $request->query->all()
                        );
                    }
                }
            }

            return $this->executeAjaxAction($request, $action, $bundleName);
        }

        return $this->sendJsonResponse(['success' => 0]);
    }

    /**
     * @return Response
     */
    public function executeAjaxAction(
        Request $request,
        $action,
        $bundle = null
    ) {
        if (method_exists($this, $action.'Action')) {
            return $this->forwardWithPost(
                static::class.'::'.$action.'Action',
                $request->request->all(),
                [
                    'action'  => $action,
                    'bundle'  => $bundle,
                ],
                $request->query->all()
            );
        }

        return $this->sendJsonResponse(['success' => 0]);
    }

    public function globalSearchAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 1];
        $searchStr = InputHelper::clean($request->query->get('global_search', ''));
        $request->getSession()->set('mautic.global_search', $searchStr);

        $event = new GlobalSearchEvent($searchStr, $this->translator);
        $this->dispatcher->dispatch($event, CoreEvents::GLOBAL_SEARCH);

        $dataArray['newContent'] = $this->renderView(
            '@MauticCore/GlobalSearch/results.html.twig',
            ['results' => $event->getResults()]
        );

        return $this->sendJsonResponse($dataArray);
    }

    public function commandListAction(Request $request): JsonResponse
    {
        $model      = InputHelper::clean($request->query->get('model'));
        $commands   = $this->getModel($model)->getCommandList();
        $dataArray  = [];
        $translator = $this->translator;
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                foreach ($c as $subc) {
                    $command = $translator->trans($k);
                    $command = (!str_contains($command, ':')) ? $command.':' : $command;

                    $dataArray[$command.$translator->trans($subc)] = ['value' => $command.$translator->trans($subc)];
                }
            } else {
                $command = $translator->trans($c);
                $command = (!str_contains($command, ':')) ? $command.':' : $command;

                $dataArray[$command] = ['value' => $command];
            }
        }
        sort($dataArray);

        return $this->sendJsonResponse($dataArray);
    }

    public function globalCommandListAction(Request $request): JsonResponse
    {
        $dispatcher = $this->dispatcher;
        $event      = new CommandListEvent();
        $dispatcher->dispatch($event, CoreEvents::BUILD_COMMAND_LIST);
        $allCommands = $event->getCommands();
        $translator  = $this->translator;
        $dataArray   = [];
        $dupChecker  = [];
        foreach ($allCommands as $commands) {
            // @todo if/when figure out a way for typeahead dynamic headers
            // $header = $translator->trans($header);
            // $dataArray[$header] = array();
            foreach ($commands as $k => $c) {
                if (is_array($c)) {
                    $command = $translator->trans($k);
                    $command = (!str_contains($command, ':')) ? $command.':' : $command;

                    foreach ($c as $subc) {
                        $subcommand = $command.$translator->trans($subc);
                        if (!in_array($subcommand, $dupChecker)) {
                            $dataArray[]  = ['value' => $subcommand];
                            $dupChecker[] = $subcommand;
                        }
                    }
                } else {
                    $command = $translator->trans($k);
                    $command = (!str_contains($command, ':')) ? $command.':' : $command;

                    if (!in_array($command, $dupChecker)) {
                        $dataArray[]  = ['value' => $command];
                        $dupChecker[] = $command;
                    }
                }
            }
            // sort($dataArray[$header]);
        }
        // ksort($dataArray);
        sort($dataArray);

        return $this->sendJsonResponse($dataArray);
    }

    public function togglePublishStatusAction(Request $request): JsonResponse
    {
        $dataArray      = ['success' => 0];
        $name           = InputHelper::clean($request->request->get('model'));
        $id             = InputHelper::clean($request->request->get('id'));
        $customToggle   = InputHelper::clean($request->request->get('customToggle'));
        $model          = $this->getModel($name);
        $status         = Response::HTTP_OK;

        $post = $request->request->all();
        unset($post['model'], $post['id'], $post['action']);
        if (!empty($post)) {
            $extra = http_build_query($post);
        } else {
            $extra = '';
        }

        $entity = $model->getEntity($id);
        if (null !== $entity) {
            $permissionBase = $model->getPermissionBase();

            $security  = $this->security;
            $createdBy = (method_exists($entity, 'getCreatedBy')) ? $entity->getCreatedBy() : null;

            if ($security->checkPermissionExists($permissionBase.':publishown')) {
                $hasPermission = $security->hasEntityAccess($permissionBase.':publishown', $permissionBase.':publishother', $createdBy);
            } elseif ($security->checkPermissionExists($permissionBase.':publish')) {
                $hasPermission = $security->isGranted($permissionBase.':publish');
            } elseif ($security->checkPermissionExists($permissionBase.':manage')) {
                $hasPermission = $security->isGranted($permissionBase.':manage');
            } elseif ($security->checkPermissionExists($permissionBase.':full')) {
                $hasPermission = $security->isGranted($permissionBase.':full');
            } elseif ($security->checkPermissionExists($permissionBase.':editown')) {
                $hasPermission = $security->hasEntityAccess($permissionBase.':editown', $permissionBase.':editother', $createdBy);
            } elseif ($security->checkPermissionExists($permissionBase.':edit')) {
                $hasPermission = $security->isGranted($permissionBase.':edit');
            } else {
                $hasPermission = false;
            }

            if ($hasPermission) {
                try {
                    $dataArray['success'] = 1;
                    // toggle permission state
                    if ($customToggle) {
                        $accessor = PropertyAccess::createPropertyAccessor();
                        $accessor->setValue($entity, $customToggle, !$accessor->getValue($entity, $customToggle));
                        $model->getRepository()->saveEntity($entity);
                    } else {
                        \assert($model instanceof FormModel);
                        $refresh = $model->togglePublishStatus($entity);
                    }
                    if (!empty($refresh)) {
                        $dataArray['reload'] = 1;
                    } else {
                        $onclickMethod  = (method_exists($entity, 'getOnclickMethod')) ? $entity->getOnclickMethod() : '';
                        $dataAttr       = (method_exists($entity, 'getDataAttributes')) ? $entity->getDataAttributes() : [];
                        $attrTransKeys  = (method_exists($entity, 'getTranslationKeysDataAttributes')) ? $entity->getTranslationKeysDataAttributes() : [];

                        // get updated icon HTML
                        $html = $this->renderView(
                            '@MauticCore/Helper/publishstatus_icon.html.twig',
                            [
                                'item'          => $entity,
                                'model'         => $name,
                                'query'         => $extra,
                                'size'          => $post['size'] ?? '',
                                'onclick'       => $onclickMethod,
                                'attributes'    => $dataAttr,
                                'transKeys'     => $attrTransKeys,
                            ]
                        );
                        $dataArray['statusHtml'] = $html;
                    }
                } catch (RecordCanNotUnpublishException $e) {
                    $this->addFlashMessage($e->getMessage());
                    $status = Response::HTTP_UNPROCESSABLE_ENTITY;
                }
            } else {
                $this->addFlashMessage('mautic.core.error.access.denied');
                $status = Response::HTTP_FORBIDDEN;
            }
        }

        $dataArray['flashes'] = $this->getFlashContent();

        return $this->sendJsonResponse($dataArray, $status);
    }

    /**
     * Unlock an entity locked by the current user.
     */
    public function unlockEntityAction(Request $request): JsonResponse
    {
        $dataArray   = ['success' => 0];
        $name        = InputHelper::clean($request->request->get('model'));
        $id          = (int) $request->request->get('id');
        $extra       = InputHelper::clean($request->request->get('parameter'));
        $model       = $this->getModel($name);
        $entity      = $model->getEntity($id);
        $currentUser = $this->user;

        if (method_exists($entity, 'getCheckedOutBy')) {
            $checkedOut = $entity->getCheckedOutBy();
            if (null !== $entity && !empty($checkedOut) && $checkedOut === $currentUser->getId()) {
                // entity exists, is checked out, and is checked out by the current user so go ahead and unlock
                \assert($model instanceof FormModel);
                $model->unlockEntity($entity, $extra);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Sets the page layout to the update layout.
     */
    public function updateSetUpdateLayoutAction(CookieHelper $cookieHelper): JsonResponse
    {
        $dataArray = [
            'success' => 1,
            'content' => $this->renderView('@MauticCore/Update/update.html.twig'),
        ];

        // A way to keep the upgrade from failing if the session is lost after
        // the cache is cleared by upgrade.php
        $cookieHelper->setCookie('mautic_update', 'setupUpdate', 300);

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Run pre-update checks, like if the user has the correct PHP version, database version, etc.
     */
    public function updateRunChecksAction(CookieHelper $cookieHelper, UpdateHelper $updateHelper): JsonResponse
    {
        $dataArray  = [];
        $translator = $this->translator;

        $coreParametersHelper = $this->coreParametersHelper;
        $errors               = [];

        if (true === $coreParametersHelper->get('composer_updates', false)) {
            $errors = [$translator->trans('mautic.core.update.composer')];
        } else {
            $results = $updateHelper->runPreUpdateChecks();

            foreach ($results as $result) {
                if (!$result->success) {
                    $errors = array_merge($errors, array_map(fn (PreUpdateCheckError $error) => $translator->trans($error->key, $error->parameters), $result->errors));
                }
            }
        }

        if (!empty($errors)) {
            $dataArray['success']    = 0;
            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.check.error');
            $dataArray['errors']     = $errors;

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->deleteCookie('mautic_update');
        } else {
            $dataArray['success']        = 1;
            $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
            $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.downloading.package');
            $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->setCookie('mautic_update', 'runChecks', 300);
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Downloads the update package.
     */
    public function updateDownloadPackageAction(UpdateHelper $updateHelper, CookieHelper $cookieHelper): JsonResponse
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        // Fetch the update package
        $update  = $updateHelper->fetchData();
        $package = $updateHelper->fetchPackage($update['package']);

        if ($package['error']) {
            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.error', ['%error%' => $translator->trans($package['message'])]);

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->deleteCookie('mautic_update');
        } else {
            $dataArray['success']        = 1;
            $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
            $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.extracting.package');
            $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->setCookie('mautic_update', 'downloadPackage', 300);
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Extracts the update package.
     */
    public function updateExtractPackageAction(UpdateHelper $updateHelper, CookieHelper $cookieHelper, PathsHelper $pathsHelper): JsonResponse
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        // Fetch the package data
        $update  = $updateHelper->fetchData();
        $zipFile = $pathsHelper->getSystemPath('cache').'/'.basename($update['package']);

        $zipper  = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if (true !== $archive) {
            $error = match ($archive) {
                \ZipArchive::ER_EXISTS => 'mautic.core.update.archive_file_exists',
                \ZipArchive::ER_INCONS, \ZipArchive::ER_INVAL, \ZipArchive::ER_MEMORY => 'mautic.core.update.archive_zip_corrupt',
                \ZipArchive::ER_NOENT => 'mautic.core.update.archive_no_such_file',
                \ZipArchive::ER_NOZIP => 'mautic.core.update.archive_not_valid_zip',
                default               => 'mautic.core.update.archive_could_not_open',
            };

            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.error', ['%error%' => $translator->trans($error)]);

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->deleteCookie('mautic_update');
        } else {
            // Extract the archive file now
            if (!$zipper->extractTo(dirname($this->getParameter('mautic.application_dir')).'/app/upgrade')) {
                $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
                $dataArray['message']    = $translator->trans(
                    'mautic.core.update.error',
                    ['%error%' => $translator->trans('mautic.core.update.error_extracting_package')]
                );
            } else {
                $zipper->close();

                $dataArray['success']        = 1;
                $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
                $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.moving.package');
                $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');

                // A way to keep the upgrade from failing if the session is lost after
                // the cache is cleared by upgrade.php
                $cookieHelper->setCookie('mautic_update', 'extractPackage', 300);
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Migrate the database to the latest version.
     */
    public function updateDatabaseMigrationAction(
        Request $request,
        PathsHelper $pathsHelper,
        LanguageHelper $languageHelper,
        CookieHelper $cookieHelper,
        LoggerInterface $mauticLogger
    ): JsonResponse {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;
        $result     = 0;

        // Also do the last bit of filesystem cleanup from the upgrade here
        if (is_dir(dirname($this->getParameter('mautic.application_dir')).'/app/upgrade')) {
            $iterator = new \FilesystemIterator(
                dirname($this->getParameter('mautic.application_dir')).'/app/upgrade', \FilesystemIterator::SKIP_DOTS
            );

            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                // Sanity checks
                if ($file->isFile()) {
                    @unlink($file->getPath().'/'.$file->getFilename());
                }
            }

            // Should be empty now, nuke the folder
            @rmdir(dirname($this->getParameter('mautic.application_dir')).'/app/upgrade');
        }

        $cacheDir = $pathsHelper->getSystemPath('cache');

        // Cleanup the update cache data now too
        if (file_exists($cacheDir.'/lastUpdateCheck.txt')) {
            @unlink($cacheDir.'/lastUpdateCheck.txt');
        }

        if (file_exists($cacheDir.'/'.MAUTIC_VERSION.'.zip')) {
            @unlink($cacheDir.'/'.MAUTIC_VERSION.'.zip');
        }

        // Update languages
        $supportedLanguages = $languageHelper->getSupportedLanguages();

        // If there is only one language, assume it is 'en_US' and skip this
        if (count($supportedLanguages) > 1) {
            // First, update the cached language data
            $result = $languageHelper->fetchLanguages(true);

            // Only continue if not in error
            if (!isset($result['error'])) {
                foreach ($supportedLanguages as $locale => $name) {
                    // We don't need to update en_US, that comes with the main package
                    if ('en_US' == $locale) {
                        continue;
                    }

                    // Update time
                    $extractResult = $languageHelper->extractLanguagePackage($locale);

                    if ($extractResult['error']) {
                        // TODO - Need to look at adding messages during update...
                    }
                }
            }
        }

        $iterator = new \FilesystemIterator($this->getParameter('mautic.application_dir').'/app/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
            $args = ['console', 'doctrine:migrations:migrate', '--no-interaction', '--env='.MAUTIC_ENV];

            if ('prod' === MAUTIC_ENV) {
                $args[] = '--no-debug';
            }

            $input       = new ArgvInput($args);
            $application = new Application($this->container->get('kernel'));
            $application->setAutoExit(false);
            $output = new BufferedOutput();

            $minExecutionTime = 300;
            $maxExecutionTime = (int) ini_get('max_execution_time');
            if ($maxExecutionTime > 0 && $maxExecutionTime < $minExecutionTime) {
                ini_set('max_execution_time', "$minExecutionTime");
            }

            $result = $application->run($input, $output);
        }

        if (0 !== $result) {
            // Log the output
            $outputBuffer = trim(preg_replace('/\n\s*\n/s', ' \\ ', $output->fetch()));
            $outputBuffer = preg_replace('/\s\s+/', ' ', trim($outputBuffer));
            $mauticLogger->log('error', '[UPGRADE ERROR] Exit code '.$result.'; '.$outputBuffer);

            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans(
                'mautic.core.update.error',
                ['%error%' => $translator->trans('mautic.core.update.error_performing_migration')]
            ).' <a href="'.$this->generateUrl('mautic_core_update_schema', ['update' => 1])
                .'" class="btn btn-primary btn-xs" data-toggle="ajax">'.$translator->trans('mautic.core.retry').'</a>';

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->deleteCookie('mautic_update');
        } else {
            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            /* @var CookieHelper $cookieHelper */
            $cookieHelper->setCookie('mautic_update', 'schemaMigration', 300);

            if ($request->get('finalize', false)) {
                // Go to the finalize step
                $dataArray['success']        = 1;
                $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
                $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.finalizing');
                $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');
            } else {
                // Upgrading from 1.0.5

                return $this->updateFinalizationAction($request, $cookieHelper);
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Finalize update.
     */
    public function updateFinalizationAction(Request $request, CookieHelper $cookieHelper): JsonResponse
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        // Here as a just in case it's needed for a future upgrade
        $dataArray['success'] = 1;
        $dataArray['message'] = $translator->trans('mautic.core.update.update_successful', ['%version%' => $this->factory->getVersion()]);

        // Check for a post install message
        if ($postMessage = $request->getSession()->get('post_upgrade_message', false)) {
            $request->getSession()->remove('post_upgrade_message');
            $postMessage              = sprintf('<h4 class="mt-lg">%s</h4><p>%s</p>', $this->translator->trans('mautic.core.update.post_message'), $postMessage);
            $dataArray['postmessage'] = $postMessage;
        }

        // Execute the mautic.post_upgrade event
        $this->dispatcher->dispatch(new UpgradeEvent($dataArray), CoreEvents::POST_UPGRADE);

        // A way to keep the upgrade from failing if the session is lost after
        // the cache is cleared by upgrade.php
        $cookieHelper->deleteCookie('mautic_update');

        // Set a redirect to force a page reload to get new menu items, assets, etc
        $dataArray['redirect'] = $this->container->get('router')->generate('mautic_core_update');

        return $this->sendJsonResponse($dataArray);
    }

    public function clearNotificationAction(Request $request): JsonResponse
    {
        $id = (int) $request->get('id', 0);

        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->getModel('core.notification');
        $model->clearNotification($id, 200);

        return $this->sendJsonResponse(['success' => 1]);
    }

    public function getBuilderTokensAction(Request $request): JsonResponse
    {
        $tokens = [];

        if (method_exists($this, 'getBuilderTokens')) {
            $query  = $request->get('query');
            $tokens = $this->getBuilderTokens($query);
        }

        return $this->sendJsonResponse($tokens);
    }

    /**
     * Fetch remote data store.
     */
    public function downloadIpLookupDataStoreAction(Request $request, IpLookupFactory $ipServiceFactory): JsonResponse
    {
        $dataArray = ['success' => 0];

        if ($request->request->has('service')) {
            $serviceName = $request->request->get('service');
            $serviceAuth = $request->request->get('auth');

            $ipService = $ipServiceFactory->getService($serviceName, $serviceAuth);

            if ($ipService instanceof AbstractLocalDataLookup) {
                if ($ipService->downloadRemoteDataStore()) {
                    $dataArray['success'] = 1;
                    $dataArray['message'] = $this->translator->trans('mautic.core.success');
                } else {
                    $remoteUrl = $ipService->getRemoteDateStoreDownloadUrl();
                    $localPath = $ipService->getLocalDataStoreFilepath();

                    if ($remoteUrl && $localPath) {
                        $dataArray['error'] = $this->translator->trans(
                            'mautic.core.ip_lookup.remote_fetch_error',
                            [
                                '%remoteUrl%' => $remoteUrl,
                                '%localPath%' => $localPath,
                            ]
                        );
                    } else {
                        $dataArray['error'] = $this->translator->trans(
                            'mautic.core.ip_lookup.remote_fetch_error_generic'
                        );
                    }
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Fetch IP Lookup form.
     */
    public function getIpLookupFormAction(Request $request, FormFactoryInterface $formFactory, IpLookupFactory $ipServiceFactory): JsonResponse
    {
        $dataArray = ['html' => '', 'attribution' => ''];

        if ($request->request->has('service')) {
            $serviceName = $request->request->get('service');

            $ipService = $ipServiceFactory->getService($serviceName);

            if ($ipService instanceof AbstractLookup) {
                $dataArray['attribution'] = $ipService->getAttribution();
                if ($ipService instanceof IpLookupFormInterface) {
                    if ($formType = $ipService->getConfigFormService()) {
                        $themes   = $ipService->getConfigFormThemes();
                        $themes[] = '@MauticCore/FormTheme/Config/config_layout.html.twig';

                        $form = $formFactory->create($formType, [], ['ip_lookup_service' => $ipService]);
                        $html = $this->renderView(
                            '@MauticCore/FormTheme/Config/ip_lookup_config_row.html.twig',
                            [
                                'form'       => $form->createView(),
                                'formThemes' => $themes,
                            ]
                        );

                        $html              = str_replace($formType.'_', 'config_coreconfig_ip_lookup_config_', $html);
                        $html              = str_replace($formType, 'config[coreconfig][ip_lookup_config]', $html);
                        $dataArray['html'] = $html;
                    }
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
