<?php
defined('MAUTIC_OFFLINE') or die('access denied');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Ajax response so set a header if applicable and bail in order to not inject raw HTML into a possible json response
    if (!headers_sent()) {
        if (!empty($submessage)) {
            $message .= " $submessage";
        }

        header("X-Mautic-System-Error: $message");
    }

    exit;
}

// Get the URLs base path
$inDev = strpos($_SERVER['SCRIPT_NAME'], 'index_dev.php') !== false;
$base  = str_replace(['index.php', 'index_dev.php'], '', $_SERVER['SCRIPT_NAME']);

// Determine if there is an asset prefix
$root = __DIR__;
include $root.'/app/config/paths.php';
$assetPrefix = $paths['asset_prefix'];
if (!empty($assetPrefix)) {
    if (substr($assetPrefix, -1) == '/') {
        $assetPrefix = substr($assetPrefix, 0, -1);
    }
}
$assetBase = $assetPrefix.$base.$paths['assets'];

// Allow a custom error page
if (file_exists(__DIR__.'/custom_offline.php')) {
    include __DIR__.'/custom_offline.php';

    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Site is offline</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $assetBase.'/images/favicon.ico'; ?>" />
    <link rel="stylesheet" href="<?php echo $assetBase.'/css/libraries.css'; ?>" />
    <link rel="stylesheet" href="<?php echo $assetBase.'/css/app.css'; ?>" />
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-offset-3 col-sm-6">
                <div class="bg-white pa-lg text-center" style="margin-top:100px;">
                    <i class="fa fa-warning fa-5x"></i>
                    <h2><?php echo $message; ?></h2>
                    <?php if (!empty($submessage)): ?>
                    <h4 class="mt-15"><?php echo $submessage; ?></h4>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>