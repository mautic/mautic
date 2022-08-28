<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CommandListEvent;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Event\UpgradeEvent;
use Mautic\CoreBundle\Exception\RecordCanNotUnpublishException;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\PreUpdateCheckError;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\CoreBundle\IpLookup\IpLookupFormInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonController
{
    /**
     * @param array $dataArray
     * @param int   $statusCode
     * @param bool  $addIgnoreWdt
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    protected function sendJsonResponse($dataArray, $statusCode = null, $addIgnoreWdt = true)
    {
        $response = new JsonResponse();

        if ('dev' == $this->container->getParameter('kernel.environment') && $addIgnoreWdt) {
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
     * @return JsonResponse
     */
    public function delegateAjaxAction()
    {
        //process ajax actions
        $authenticationChecker = $this->get('security.authorization_checker');
        $action                = $this->request->get('action');
        $bundleName            = null;
        if (empty($action)) {
            //check POST
            $action = $this->request->request->get('action');
        }

        if ($authenticationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if (false !== strpos($action, ':')) {
                //call the specified bundle's ajax action
                $parts     = explode(':', $action);
                $namespace = 'Mautic';
                $isPlugin  = false;

                if (3 == count($parts) && 'plugin' == $parts['0']) {
                    $namespace = 'MauticPlugin';
                    array_shift($parts);
                    $isPlugin = true;
                }

                if (2 == count($parts)) {
                    $bundleName = $parts[0];
                    $bundle     = ucfirst($bundleName);
                    $action     = $parts[1];

                    if (!$classExists = class_exists($namespace.'\\'.$bundle.'Bundle\\Controller\\AjaxController')) {
                        // Check if a plugin is prefixed with Mautic
                        $bundle      = 'Mautic'.$bundle;
                        $classExists = class_exists($namespace.'\\'.$bundle.'Bundle\\Controller\\AjaxController');
                    } elseif (!$isPlugin && 'Marketplace' !== $bundle) {
                        $bundle = 'Mautic'.$bundle;
                    }

                    if ($classExists) {
                        return $this->forward(
                            "{$bundle}Bundle:Ajax:executeAjax",
                            [
                                'action' => $action,
                                //forward the request as well as Symfony creates a subrequest without GET/POST
                                'request' => $this->request,
                                'bundle'  => $bundleName,
                            ]
                        );
                    }
                }
            }

            return $this->executeAjaxAction($action, $this->request, $bundleName);
        }

        return $this->sendJsonResponse(['success' => 0]);
    }

    /**
     * @param      $action
     * @param null $bundle
     *
     * @return JsonResponse
     */
    public function executeAjaxAction($action, Request $request, $bundle = null)
    {
        if (method_exists($this, "{$action}Action")) {
            return $this->{"{$action}Action"}($request, $bundle);
        }

        return $this->sendJsonResponse(['success' => 0]);
    }

    /**
     * @return JsonResponse
     */
    protected function globalSearchAction(Request $request)
    {
        $dataArray = ['success' => 1];
        $searchStr = InputHelper::clean($request->query->get('global_search', ''));
        $this->get('session')->set('mautic.global_search', $searchStr);

        $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
        $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);

        $dataArray['newContent'] = $this->renderView(
            'MauticCoreBundle:GlobalSearch:results.html.php',
            ['results' => $event->getResults()]
        );

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return JsonResponse
     */
    protected function commandListAction(Request $request)
    {
        $model      = InputHelper::clean($request->query->get('model'));
        $commands   = $this->getModel($model)->getCommandList();
        $dataArray  = [];
        $translator = $this->get('translator');
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                foreach ($c as $subc) {
                    $command = $translator->trans($k);
                    $command = (false === strpos($command, ':')) ? $command.':' : $command;

                    $dataArray[$command.$translator->trans($subc)] = ['value' => $command.$translator->trans($subc)];
                }
            } else {
                $command = $translator->trans($c);
                $command = (false === strpos($command, ':')) ? $command.':' : $command;

                $dataArray[$command] = ['value' => $command];
            }
        }
        sort($dataArray);

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return JsonResponse
     */
    protected function globalCommandListAction(Request $request)
    {
        $dispatcher = $this->get('event_dispatcher');
        $event      = new CommandListEvent();
        $dispatcher->dispatch(CoreEvents::BUILD_COMMAND_LIST, $event);
        $allCommands = $event->getCommands();
        $translator  = $this->get('translator');
        $dataArray   = [];
        $dupChecker  = [];
        foreach ($allCommands as $commands) {
            //@todo if/when figure out a way for typeahead dynamic headers
            //$header = $translator->trans($header);
            //$dataArray[$header] = array();
            foreach ($commands as $k => $c) {
                if (is_array($c)) {
                    $command = $translator->trans($k);
                    $command = (false === strpos($command, ':')) ? $command.':' : $command;

                    foreach ($c as $subc) {
                        $subcommand = $command.$translator->trans($subc);
                        if (!in_array($subcommand, $dupChecker)) {
                            $dataArray[]  = ['value' => $subcommand];
                            $dupChecker[] = $subcommand;
                        }
                    }
                } else {
                    $command = $translator->trans($k);
                    $command = (false === strpos($command, ':')) ? $command.':' : $command;

                    if (!in_array($command, $dupChecker)) {
                        $dataArray[]  = ['value' => $command];
                        $dupChecker[] = $command;
                    }
                }
            }
            //sort($dataArray[$header]);
        }
        //ksort($dataArray);
        sort($dataArray);

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return JsonResponse
     */
    protected function togglePublishStatusAction(Request $request)
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

            /** @var CorePermissions $security */
            $security  = $this->get('mautic.security');
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
                    //toggle permission state
                    if ($customToggle) {
                        $accessor = PropertyAccess::createPropertyAccessor();
                        $accessor->setValue($entity, $customToggle, !$accessor->getValue($entity, $customToggle));
                        $model->getRepository()->saveEntity($entity);
                    } else {
                        $refresh = $model->togglePublishStatus($entity);
                    }
                    if (!empty($refresh)) {
                        $dataArray['reload'] = 1;
                    } else {
                        $onclickMethod  = (method_exists($entity, 'getOnclickMethod')) ? $entity->getOnclickMethod() : '';
                        $dataAttr       = (method_exists($entity, 'getDataAttributes')) ? $entity->getDataAttributes() : [];
                        $attrTransKeys  = (method_exists($entity, 'getTranslationKeysDataAttributes')) ? $entity->getTranslationKeysDataAttributes() : [];

                        //get updated icon HTML
                        $html = $this->renderView(
                            'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                            [
                                'item'          => $entity,
                                'model'         => $name,
                                'query'         => $extra,
                                'size'          => (isset($post['size'])) ? $post['size'] : '',
                                'onclick'       => $onclickMethod,
                                'attributes'    => $dataAttr,
                                'transKeys'     => $attrTransKeys,
                            ]
                        );
                        $dataArray['statusHtml'] = $html;
                    }
                } catch (RecordCanNotUnpublishException $e) {
                    $this->addFlash($e->getMessage());
                    $status = Response::HTTP_UNPROCESSABLE_ENTITY;
                }
            } else {
                $this->addFlash('mautic.core.error.access.denied');
                $status = Response::HTTP_FORBIDDEN;
            }
        }

        $dataArray['flashes'] = $this->getFlashContent();

        return $this->sendJsonResponse($dataArray, $status);
    }

    /**
     * Unlock an entity locked by the current user.
     *
     * @return JsonResponse
     */
    protected function unlockEntityAction(Request $request)
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
                //entity exists, is checked out, and is checked out by the current user so go ahead and unlock
                $model->unlockEntity($entity, $extra);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Sets the page layout to the update layout.
     *
     * @return JsonResponse
     */
    protected function updateSetUpdateLayoutAction(Request $request)
    {
        $dataArray = [
            'success' => 1,
            'content' => $this->renderView('MauticCoreBundle:Update:update.html.php'),
        ];

        // A way to keep the upgrade from failing if the session is lost after
        // the cache is cleared by upgrade.php
        /** @var \Mautic\CoreBundle\Helper\CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');
        $cookieHelper->setCookie('mautic_update', 'setupUpdate', 300);

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Run pre-update checks, like if the user has the correct PHP version, database version, etc.
     */
    protected function updateRunChecksAction(): JsonResponse
    {
        $dataArray  = [];
        $translator = $this->translator;
        /** @var \Mautic\CoreBundle\Helper\CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');
        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->container->get('mautic.helper.update');
        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = $this->container->get('mautic.helper.core_parameters');
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
     *
     * @return JsonResponse
     */
    protected function updateDownloadPackageAction(Request $request)
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->container->get('mautic.helper.update');

        // Fetch the update package
        $update  = $updateHelper->fetchData();
        $package = $updateHelper->fetchPackage($update['package']);

        /** @var \Mautic\CoreBundle\Helper\CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');

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
     *
     * @return JsonResponse
     */
    protected function updateExtractPackageAction(Request $request)
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        /** @var UpdateHelper $updateHelper */
        $updateHelper = $this->container->get('mautic.helper.update');

        /** @var CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');

        /** @var PathsHelper $pathsHelper */
        $pathsHelper = $this->container->get('mautic.helper.paths');

        // Fetch the package data
        $update  = $updateHelper->fetchData();
        $zipFile = $pathsHelper->getSystemPath('cache').'/'.basename($update['package']);

        $zipper  = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if (true !== $archive) {
            // Get the exact error
            switch ($archive) {
                case \ZipArchive::ER_EXISTS:
                    $error = 'mautic.core.update.archive_file_exists';
                    break;
                case \ZipArchive::ER_INCONS:
                case \ZipArchive::ER_INVAL:
                case \ZipArchive::ER_MEMORY:
                    $error = 'mautic.core.update.archive_zip_corrupt';
                    break;
                case \ZipArchive::ER_NOENT:
                    $error = 'mautic.core.update.archive_no_such_file';
                    break;
                case \ZipArchive::ER_NOZIP:
                    $error = 'mautic.core.update.archive_not_valid_zip';
                    break;
                case \ZipArchive::ER_READ:
                case \ZipArchive::ER_SEEK:
                case \ZipArchive::ER_OPEN:
                default:
                    $error = 'mautic.core.update.archive_could_not_open';
                    break;
            }

            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.error', ['%error%' => $translator->trans($error)]);

            // A way to keep the upgrade from failing if the session is lost after
            // the cache is cleared by upgrade.php
            $cookieHelper->deleteCookie('mautic_update');
        } else {
            // Extract the archive file now
            if (!$zipper->extractTo(dirname($this->container->getParameter('kernel.root_dir')).'/upgrade')) {
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
     *
     * @return JsonResponse
     */
    public function updateDatabaseMigrationAction(Request $request)
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;
        $result     = 0;

        // Also do the last bit of filesystem cleanup from the upgrade here
        if (is_dir(dirname($this->container->getParameter('kernel.root_dir')).'/upgrade')) {
            $iterator = new \FilesystemIterator(
                dirname($this->container->getParameter('kernel.root_dir')).'/upgrade', \FilesystemIterator::SKIP_DOTS
            );

            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                // Sanity checks
                if ($file->isFile()) {
                    @unlink($file->getPath().'/'.$file->getFilename());
                }
            }

            // Should be empty now, nuke the folder
            @rmdir(dirname($this->container->getParameter('kernel.root_dir')).'/upgrade');
        }

        $cacheDir = $this->container->get('mautic.helper.paths')->getSystemPath('cache');

        // Cleanup the update cache data now too
        if (file_exists($cacheDir.'/lastUpdateCheck.txt')) {
            @unlink($cacheDir.'/lastUpdateCheck.txt');
        }

        if (file_exists($cacheDir.'/'.MAUTIC_VERSION.'.zip')) {
            @unlink($cacheDir.'/'.MAUTIC_VERSION.'.zip');
        }

        // Update languages
        /** @var LanguageHelper $languageHelper */
        $languageHelper     = $this->container->get('mautic.helper.language');
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

        $iterator = new \FilesystemIterator($this->container->getParameter('kernel.root_dir').'/migrations', \FilesystemIterator::SKIP_DOTS);

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

        /** @var CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');

        if (0 !== $result) {
            // Log the output
            $outputBuffer = trim(preg_replace('/\n\s*\n/s', ' \\ ', $output->fetch()));
            $outputBuffer = preg_replace('/\s\s+/', ' ', trim($outputBuffer));
            $this->container->get('monolog.logger.mautic')->log('error', '[UPGRADE ERROR] Exit code '.$result.'; '.$outputBuffer);

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

                return $this->updateFinalizationAction($request);
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Finalize update.
     *
     * @return JsonResponse
     */
    public function updateFinalizationAction(Request $request)
    {
        $dataArray  = ['success' => 0];
        $translator = $this->translator;

        // Here as a just in case it's needed for a future upgrade
        $dataArray['success'] = 1;
        $dataArray['message'] = $translator->trans('mautic.core.update.update_successful', ['%version%' => $this->factory->getVersion()]);

        // Check for a post install message
        if ($postMessage = $this->container->get('session')->get('post_upgrade_message', false)) {
            $this->container->get('session')->remove('post_upgrade_message');
            $postMessage              = sprintf('<h4 class="mt-lg">%s</h4><p>%s</p>', $this->container->get('translator')->trans('mautic.core.update.post_message'), $postMessage);
            $dataArray['postmessage'] = $postMessage;
        }

        // Execute the mautic.post_upgrade event
        $this->dispatcher->dispatch(CoreEvents::POST_UPGRADE, new UpgradeEvent($dataArray));

        // A way to keep the upgrade from failing if the session is lost after
        // the cache is cleared by upgrade.php
        /** @var CookieHelper $cookieHelper */
        $cookieHelper = $this->container->get('mautic.helper.cookie');
        $cookieHelper->deleteCookie('mautic_update');

        // Set a redirect to force a page reload to get new menu items, assets, etc
        $dataArray['redirect'] = $this->container->get('router')->generate('mautic_core_update');

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return JsonResponse
     */
    protected function clearNotificationAction(Request $request)
    {
        $id = (int) $request->get('id', 0);

        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->getModel('core.notification');
        $model->clearNotification($id, 200);

        return $this->sendJsonResponse(['success' => 1]);
    }

    /**
     * @return JsonResponse
     */
    protected function getBuilderTokensAction(Request $request)
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
     *
     * @return JsonResponse
     */
    protected function downloadIpLookupDataStoreAction(Request $request)
    {
        $dataArray = ['success' => 0];

        if ($request->request->has('service')) {
            $serviceName = $request->request->get('service');
            $serviceAuth = $request->request->get('auth');

            /** @var \Mautic\CoreBundle\Factory\IpLookupFactory $ipServiceFactory */
            $ipServiceFactory = $this->container->get('mautic.ip_lookup.factory');
            $ipService        = $ipServiceFactory->getService($serviceName, $serviceAuth);

            if ($ipService instanceof AbstractLocalDataLookup) {
                if ($ipService->downloadRemoteDataStore()) {
                    $dataArray['success'] = 1;
                    $dataArray['message'] = $this->container->get('translator')->trans('mautic.core.success');
                } else {
                    $remoteUrl = $ipService->getRemoteDateStoreDownloadUrl();
                    $localPath = $ipService->getLocalDataStoreFilepath();

                    if ($remoteUrl && $localPath) {
                        $dataArray['error'] = $this->container->get('translator')->trans(
                            'mautic.core.ip_lookup.remote_fetch_error',
                            [
                                '%remoteUrl%' => $remoteUrl,
                                '%localPath%' => $localPath,
                            ]
                        );
                    } else {
                        $dataArray['error'] = $this->container->get('translator')->trans(
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
     *
     * @return JsonResponse
     */
    protected function getIpLookupFormAction(Request $request)
    {
        $dataArray = ['html' => '', 'attribution' => ''];

        if ($request->request->has('service')) {
            $serviceName = $request->request->get('service');

            /** @var \Mautic\CoreBundle\Factory\IpLookupFactory $ipServiceFactory */
            $ipServiceFactory = $this->container->get('mautic.ip_lookup.factory');
            $ipService        = $ipServiceFactory->getService($serviceName);

            if ($ipService instanceof AbstractLookup) {
                $dataArray['attribution'] = $ipService->getAttribution();
                if ($ipService instanceof IpLookupFormInterface) {
                    if ($formType = $ipService->getConfigFormService()) {
                        $themes   = $ipService->getConfigFormThemes();
                        $themes[] = 'MauticCoreBundle:FormTheme\Config';

                        $form = $this->get('form.factory')->create($formType, [], ['ip_lookup_service' => $ipService]);
                        $html = $this->renderView(
                            'MauticCoreBundle:FormTheme\Config:ip_lookup_config_row.html.php',
                            [
                                'form' => $this->setFormTheme($form, 'MauticCoreBundle:FormTheme\Config:ip_lookup_config_row.html.php', $themes),
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
