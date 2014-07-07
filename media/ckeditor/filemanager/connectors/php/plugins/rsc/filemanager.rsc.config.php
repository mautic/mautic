<?php
/**
*	Filemanager PHP RSC plugin configuration
*
*	filemanager.rsc.class.php
*	This is a separate config file for the parameters needed for the RSC plugin
*	You may over-ride any parameters set by the filemanager.config.php file here
*
*	@license	MIT License
*	@author		Alan Blount <alan (at) zeroasterisk (dot) com>
*	@copyright	Authors
*/	

/**
 *	Language settings
 */
$config['rsc-verbose'] = false;

/**
 *	Language settings
 */
$config['rsc-username'] = 'your_username';

/**
 *	Language settings
 */
$config['rsc-apikey'] = 'your_api_key_hash';

/**
 *	RSC Account (optional)
 */
$config['rsc-account'] = null;

/**
 *	RSC Account (optionally limit container to this, better accomplished by limiting the base path)
 */
$config['rsc-container'] = null;

/**
 *	Language settings
 */
$config['rsc-ssl_use_cabundle'] = true;

/**
 *	Language settings
 */
$config['rsc-getsize'] = true;

/**
 *	Extension of the unallowed Dirs
 */
$config['unallowed_dirs'][] = '.CDN_ACCESS_LOGS';
$config['unallowed_dirs'][] = 'cloudservers';


?>
