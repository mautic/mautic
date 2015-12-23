<?php
/**
 *	Filemanager PHP connector
 *  This file should at least declare auth() function
 *  and instantiate the Filemanager as '$fm'
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

// Boot Symfony
require_once __DIR__ . '/../../../../../../../../../bootstrap.php.cache';
require_once __DIR__ . '/../../../../../../../../../AppKernel.php';
$kernel = new AppKernel('prod', false);
$kernel->boot();
$session = $kernel->getContainer()->get('session');
$userId  = $session->get('mautic.user', false);

/**
 *	Check if user is authorized
 *
 *
 *	@return boolean true if access granted, false if no access
 */
function auth() {
    global $userId;
    // You can insert your own code over here to check if the user is authorized.
    // If you use a session variable, you've got to start the session first (session_start())

    return (!empty($userId));
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

if ($userId) {
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