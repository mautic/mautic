<?php

/*
 *	Filemanager PHP connector
 *  This file should at least declare auth() function
 *  and instantiate the Filemanager as '$fm'.
 *
 *  IMPORTANT : by default Read and Write access is granted to everyone
 *  Copy/paste this file to 'user.config.php' file to implement your own auth() function
 *  to grant access to wanted users only
 *
 *	filemanager.php
 *	use for ckeditor filemanager
 *
 *	@license	MIT License
 *  @author		Simon Georget <simon (at) linea21 (dot) com>
 *	@copyright	Authors
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

// Boot Symfony
try {
    require_once __DIR__.'/../../../../../../../../../autoload.php';
    require_once __DIR__.'/../../../../../../../../../bootstrap.php.cache';
    require_once __DIR__.'/../../../../../../../../../AppKernel.php';

    \Mautic\CoreBundle\ErrorHandler\ErrorHandler::register('prod');

    $kernel = new AppKernel('prod', false);
    $kernel->boot();
    $container = $kernel->getContainer();
    $request   = Request::createFromGlobals();
    $container->enterScope('request');
    $container->set('request', $request, 'request');

    // Dispatch REQUEST event to setup authentication
    $httpKernel = $container->get('http_kernel');
    $event      = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST);
    $container->get('event_dispatcher')->dispatch(KernelEvents::REQUEST, $event);

    $session       = $container->get('session');
    $securityToken = $container->get('security.token_storage');
    $token         = $securityToken->getToken();
    $authenticated = ($token instanceof TokenInterface) ? count($token->getRoles()) : false;
} catch (\Exception $exception) {
    error_log($exception);
    $authenticated = false;
}

/**
 *	Check if user is authorized.
 *
 *
 *	@return bool true if access granted, false if no access
 */
function auth()
{
    global $authenticated;
    // You can insert your own code over here to check if the user is authorized.
    // If you use a session variable, you've got to start the session first (session_start())

    return $authenticated;
}

// @todo Work on plugins registration
// if (isset($config['plugin']) && !empty($config['plugin'])) {
// 	$pluginPath = 'plugins' . DIRECTORY_SEPARATOR . $config['plugin'] . DIRECTORY_SEPARATOR;
// 	require_once($pluginPath . 'filemanager.' . $config['plugin'] . '.config.php');
// 	require_once($pluginPath . 'filemanager.' . $config['plugin'] . '.class.php');
// 	$className = 'Filemanager'.strtoupper($config['plugin']);
// 	$fm = new $className($config);
// } else {
// 	$fm = new Filemanager($config);
// }

$fm = new Filemanager();

if ($authenticated) {
    $userDir = $session->get('mautic.imagepath', false);
    $baseDir = $session->get('mautic.basepath', false);
    $docRoot = $session->get('mautic.docroot', false);

    if (substr($userDir, -1) !== '/') {
        $userDir .= '/';
    }

    if ($baseDir && $baseDir != '/') {
        if (substr($baseDir, 0, 1) == '/') {
            $baseDir = substr($baseDir, 1);
        }

        if (substr($baseDir, -1) == '/') {
            $baseDir = substr($baseDir, 0, -1);
        }

        if (substr($userDir, 0, 1) == '/') {
            $userDir = substr($userDir, 1);
        }

        $userDir = $baseDir.'/'.$userDir;
    } elseif (substr($userDir, 0, 1) == '/') {
        $userDir = substr($userDir, 1);
    }

    $fm->setFileRoot($userDir, $docRoot);
}
