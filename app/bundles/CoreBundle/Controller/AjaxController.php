<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Event\CommandListEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Swiftmailer\Transport\AmazonTransport;
use Mautic\CoreBundle\Swiftmailer\Transport\MandrillTransport;
use Mautic\CoreBundle\Swiftmailer\Transport\PostmarkTransport;
use Mautic\CoreBundle\Swiftmailer\Transport\SendgridTransport;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonController
{

    /**
     * @param array $dataArray
     *
     * @return JsonResponse
     * @throws \Exception
     */
    protected function sendJsonResponse($dataArray)
    {
        $response  = new JsonResponse();

        if ($this->factory->getEnvironment() == 'dev') {
            $dataArray['ignore_wdt'] = 1;
        }
        $response->setData($dataArray);

        return $response;
    }

    /**
     * Executes an action requested via ajax
     *
     * @return JsonResponse
     */
    public function delegateAjaxAction()
    {
        //process ajax actions
        $securityContext = $this->factory->getSecurityContext();
        $action          = $this->request->get('action');
        if (empty($action)) {
            //check POST
            $action = $this->request->request->get('action');
        }

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if (strpos($action, ":") !== false) {
                //call the specified bundle's ajax action
                $parts = explode(":", $action);
                $namespace = 'Mautic';
                $isAddon = false;

                if ($parts[0] == 'addon' && count($parts) == 3) {
                    $namespace = 'MauticAddon';
                    array_shift($parts);
                    $isAddon = true;
                }

                if (count($parts) == 2) {
                    $bundle     = ucfirst($parts[0]);
                    $action     = $parts[1];

                    if (class_exists($namespace . '\\' . $bundle . 'Bundle\\Controller\\AjaxController')) {
                        if (!$isAddon) {
                            $bundle = 'Mautic' . $bundle;
                        }
                        return $this->forward("{$bundle}Bundle:Ajax:executeAjax", array(
                            'action'  => $action,
                            //forward the request as well as Symfony creates a subrequest without GET/POST
                            'request' => $this->request
                        ));
                    }
                }
            }

            return $this->executeAjaxAction($action, $this->request);
        }

        return $this->sendJsonResponse(array('success' => 0));
    }

    /**
     * @param string  $action
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function executeAjaxAction($action, Request $request)
    {
        if (method_exists($this, "{$action}Action")) {
            return $this->{"{$action}Action"}($request);
        }

        return $this->sendJsonResponse(array('success' => 0));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function globalSearchAction(Request $request)
    {
        $dataArray = array('success' => 1);
        $searchStr = InputHelper::clean($request->query->get("global_search", ""));
        $this->factory->getSession()->set('mautic.global_search', $searchStr);

        $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
        $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);

        $dataArray['newContent'] = $this->renderView('MauticCoreBundle:GlobalSearch:results.html.php',
            array('results' => $event->getResults())
        );
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function commandListAction(Request $request)
    {
        $model      = InputHelper::clean($request->query->get('model'));
        $commands   = $this->factory->getModel($model)->getCommandList();
        $dataArray  = array();
        $translator = $this->get('translator');
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                foreach ($c as $subc) {
                    $command = $translator->trans($k);
                    $command = (strpos($command, ':') === false) ? $command . ':' : $command;

                    $dataArray[] = array('value' => $command . $translator->trans($subc));
                }
            } else {
                $command = $translator->trans($c);
                $command = (strpos($command, ':') === false) ? $command . ':' : $command;

                $dataArray[] = array('value' => $command);
            }
        }
        sort($dataArray);
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function globalCommandListAction(Request $request)
    {
        $dispatcher = $this->get('event_dispatcher');
        $event = new CommandListEvent();
        $dispatcher->dispatch(CoreEvents::BUILD_COMMAND_LIST, $event);
        $allCommands = $event->getCommands();
        $translator  = $this->get('translator');
        $dataArray   = array();
        $dupChecker  = array();
        foreach ($allCommands as $header => $commands) {
            //@todo if/when figure out a way for typeahead dynamic headers
            //$header = $translator->trans($header);
            //$dataArray[$header] = array();
            foreach ($commands as $k => $c) {
                if (is_array($c)) {
                    $command = $translator->trans($k);
                    $command = (strpos($command, ':') === false) ? $command . ':' : $command;

                    foreach ($c as $subc) {
                        $subcommand = $command . $translator->trans($subc);
                        if (!in_array($subcommand, $dupChecker)) {
                            $dataArray[] = array('value' => $subcommand);
                            $dupChecker[] = $subcommand;
                        }
                    }
                } else {
                    $command = $translator->trans($k);
                    $command = (strpos($command, ':') === false) ? $command . ':' : $command;

                    if (!in_array($command, $dupChecker)) {
                        $dataArray[] = array('value' => $command);
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function togglePublishStatusAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $name   = InputHelper::clean($request->request->get('model'));
        if (strpos($name, '.') === false) {
            $name = "$name.$name";
        }
        $id     = InputHelper::int($request->request->get('id'));
        $model  = $this->factory->getModel($name);

        $post = $request->request->all();
        unset($post['model'], $post['id'], $post['action']);
        if (!empty($post)) {
            $extra = http_build_query($post);
        } else {
            $extra = '';
        }

        $entity = $model->getEntity($id);
        if ($entity !== null) {
            $permissionBase = $model->getPermissionBase();
            $security = $this->factory->getSecurity();
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
                $dataArray['success'] = 1;
                //toggle permission state
                $refresh = $model->togglePublishStatus($entity);

                if ($refresh) {
                    $dataArray['reload'] = 1;
                } else {
                    //get updated icon HTML
                    $html = $this->renderView('MauticCoreBundle:Helper:publishstatus_icon.html.php', array(
                        'item'  => $entity,
                        'model' => $name,
                        'query' => $extra,
                        'size'  => (isset($post['size'])) ? $post['size'] : ''
                    ));
                    $dataArray['statusHtml'] = $html;
                }
            }
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Unlock an entity locked by the current user
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function unlockEntityAction(Request $request)
    {
        $dataArray   = array('success' => 0);
        $name        = InputHelper::clean($request->request->get('model'));
        $id          = InputHelper::int($request->request->get('id'));
        $extra       = InputHelper::clean($request->request->get('parameter'));
        $model       = $this->factory->getModel($name);
        $entity      = $model->getEntity($id);
        $currentUser = $this->factory->getUser();

        if (method_exists($entity, 'getCheckedOutBy')) {

            $checkedOut = $entity->getCheckedOutBy();
            if ($entity !== null && !empty($checkedOut) && $checkedOut === $currentUser->getId()) {
                //entity exists, is checked out, and is checked out by the current user so go ahead and unlock
                $model->unlockEntity($entity, $extra);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Sets the page layout to the update layout
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function updateSetUpdateLayoutAction(Request $request)
    {
        $dataArray = array(
            'success' => 1,
            'content' => $this->renderView('MauticCoreBundle:Update:update.html.php')
        );

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Downloads the update package
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function updateDownloadPackageAction(Request $request)
    {
        $dataArray  = array('success' => 0);
        $translator = $this->factory->getTranslator();

        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->factory->getHelper('update');

        // Fetch the update package
        $update  = $updateHelper->fetchData();
        $package = $updateHelper->fetchPackage($update['package']);

        if ($package['error']) {
            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.error', array('%error%' => $translator->trans($package['message'])));
        } else {
            $dataArray['success']        = 1;
            $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
            $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.extracting.package');
            $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Extracts the update package
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function updateExtractPackageAction(Request $request)
    {
        $dataArray  = array('success' => 0);
        $translator = $this->factory->getTranslator();

        /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
        $updateHelper = $this->factory->getHelper('update');

        // Fetch the package data
        $update  = $updateHelper->fetchData();
        $zipFile = $this->factory->getSystemPath('cache') . '/' . basename($update['package']);

        $zipper = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if ($archive !== true) {
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
            $dataArray['message']    = $translator->trans('mautic.core.update.error', array('%error%' => $translator->trans($error)));
        } else {
            // Extract the archive file now
            $zipper->extractTo(dirname($this->container->getParameter('kernel.root_dir')) . '/upgrade');
            $zipper->close();

            $dataArray['success']        = 1;
            $dataArray['stepStatus']     = $translator->trans('mautic.core.update.step.success');
            $dataArray['nextStep']       = $translator->trans('mautic.core.update.step.moving.package');
            $dataArray['nextStepStatus'] = $translator->trans('mautic.core.update.step.in.progress');
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Migrate the database to the latest version
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function updateDatabaseMigrationAction(Request $request)
    {
        $dataArray  = array('success' => 0);
        $translator = $this->factory->getTranslator();
        $result     = 0;

        // Also do the last bit of filesystem cleanup from the upgrade here
        if (is_dir(dirname($this->container->getParameter('kernel.root_dir')) . '/upgrade')) {
            $iterator = new \FilesystemIterator(dirname($this->container->getParameter('kernel.root_dir')) . '/upgrade', \FilesystemIterator::SKIP_DOTS);

            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                // Sanity checks
                if ($file->isFile()) {
                    @unlink($file->getPath() . '/' . $file->getFilename());
                }
            }

            // Should be empty now, nuke the folder
            @rmdir(dirname($this->container->getParameter('kernel.root_dir')) . '/upgrade');
        }

        $cacheDir = $this->factory->getSystemPath('cache');

        // Cleanup the update cache data now too
        if (file_exists($cacheDir . '/lastUpdateCheck.txt')) {
            @unlink($cacheDir . '/lastUpdateCheck.txt');
        }

        if (file_exists($cacheDir . '/' . $this->factory->getVersion() . '.zip')) {
            @unlink($cacheDir . '/' . $this->factory->getVersion() . '.zip');
        }

        // Update languages
        $supportedLanguages = $this->factory->getParameter('supported_languages');

        // If there is only one language, assume it is 'en_US' and skip this
        if (count($supportedLanguages) > 1) {
            /** @var \Mautic\CoreBundle\Helper\LanguageHelper $languageHelper */
            $languageHelper = $this->factory->getHelper('language');

            // First, update the cached language data
            $result = $languageHelper->fetchLanguages(true);

            // Only continue if not in error
            if (!isset($result['error'])) {
                foreach ($supportedLanguages as $locale => $name) {
                    // We don't need to update en_US, that comes with the main package
                    if ($locale == 'en_US') {
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

        $iterator = new \FilesystemIterator($this->container->getParameter('kernel.root_dir') . '/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
            $env         = $this->factory->getEnvironment();
            $args        = array('console', 'doctrine:migrations:migrate', '--no-interaction', '--env=' . $env);

            if ($env == 'prod') {
                $args[] = '--no-debug';
            }

            $input       = new ArgvInput($args);
            $application = new Application($this->get('kernel'));
            $application->setAutoExit(false);
            $output      = new NullOutput();
            $result      = $application->run($input, $output);
        }

        if ($result !== 0) {
            $dataArray['stepStatus'] = $translator->trans('mautic.core.update.step.failed');
            $dataArray['message']    = $translator->trans('mautic.core.update.error', array('%error%' => $translator->trans('mautic.core.update.error_performing_migration')));
        } else {
            $dataArray['success'] = 1;
            $dataArray['message'] = $translator->trans('mautic.core.update.update_successful', array('%version%' => $this->factory->getVersion()));
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function updateUserStatusAction(Request $request)
    {
        $status = InputHelper::clean($request->request->get('status'));

        /** @var \Mautic\UserBundle\Model\UserModel $model */
        $model = $this->factory->getModel('user');

        $currentStatus = $this->factory->getUser()->getOnlineStatus();
        if (!in_array($currentStatus, array('manualaway', 'dnd'))) {
            if ($status == 'back') {
                $status = 'online';
            }

            $model->setOnlineStatus($status);
        }

        return $this->sendJsonResponse(array('success' => 1));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function markNotificationsReadAction(Request $request)
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->factory->getModel('core.notification');

        $model->markAllRead();

        return $this->sendJsonResponse(array('success' => 1));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function clearNotificationAction(Request $request)
    {
        $id = InputHelper::int($request->get('id', 0));

        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->factory->getModel('core.notification');
        $model->clearNotification($id);

        return $this->sendJsonResponse(array('success' => 1));
    }

    /**
     * Tests mail transport settings
     *
     * @param Request $request
     */
    protected function testEmailServerConnectionAction(Request $request)
    {
        $dataArray = array('success' => 0, 'error' => '');

        if ($this->factory->getUser()->isAdmin()) {
            $settings = $request->request->all();

            $transport = $settings['transport'];

            switch($transport) {
                case 'mautic.transport.mandrill':
                    $mailer = new MandrillTransport();
                    break;
                case 'mautic.transport.sendgrid':
                    $mailer = new SendgridTransport();
                    break;
                case 'mautic.transport.amazon':
                    $mailer = new AmazonTransport();
                    break;
                case 'mautic.transport.postmark':
                    $mailer = new PostmarkTransport();
                    break;
                case 'gmail':
                    $mailer = new \Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl');
                    break;
                case 'smtp':
                    $mailer = new \Swift_SmtpTransport($settings['host'], $settings['port'], $settings['encryption']);
                    break;
            }

            if (!empty($mailer)) {
                if (empty($settings['password'])) {
                    $settings['password'] = $this->factory->getParameter('mailer_password');
                }
                $mailer->setUsername($settings['user']);
                $mailer->setPassword($settings['password']);

                $logger = new \Swift_Plugins_Loggers_ArrayLogger();
                $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

                try {
                    $mailer->start();
                    $translator = $this->factory->getTranslator();

                    if ($settings['send_test'] == 'true') {
                        $message = new \Swift_Message(
                            $translator->trans('mautic.core.config.form.mailer.transport.test_send.subject'),
                            $translator->trans('mautic.core.config.form.mailer.transport.test_send.body')
                        );

                        $user = $this->factory->getUser();

                        $message->setFrom(array($settings['from_email'] => $settings['from_name']));
                        $message->setTo(array($user->getEmail() => $user->getFirstName().' '.$user->getLastName()));

                        $mailer->send($message);
                    }

                    $dataArray['success'] = 1;
                    $dataArray['message'] = $translator->trans('mautic.core.success');

                } catch (\Exception $e) {
                    $dataArray['message'] = $e->getMessage() . '<br />' . $logger->dump();
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
