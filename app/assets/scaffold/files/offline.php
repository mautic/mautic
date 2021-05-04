<?php
defined('MAUTIC_OFFLINE') or die('access denied');

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

if (empty($inline)): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Site is offline</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $assetBase.'/images/favicon.ico'; ?>"/>
    <link rel="stylesheet" href="<?php echo $assetBase.'/css/libraries.css'; ?>"/>
    <link rel="stylesheet" href="<?php echo $assetBase.'/css/app.css'; ?>"/>
</head>

<body>
<div class="container">
<?php endif; ?>
    <div class="row">
        <?php if (!empty($error)): ?>

        <div class="<?php echo (!empty($error['isPrevious']) || $inline) ? 'col-sm-12' : 'col-sm-offset-2 col-sm-8'; ?>">
            <div class="bg-white pa-sm" style=" word-wrap: break-word;<?php if (empty($error['isPrevious']) && !$inline): ?> margin-top:100px;<?php endif; ?>">
                <?php if ($inline): ?>
                <h3><i class="fa fa-warning fa-fw text-danger pull-left"></i><?php echo $error['message']; ?></h3>
                <h6 class="text-muted"><?php echo $error['file'].':'.$error['line']; ?></h6>
                <?php else: ?>
                <div class="text-center"><i class="fa fa-warning fa-5x"></i></div>
                <div class="alert alert-danger">
                    <div><?php echo $error['message']; ?></div>
                    <div class="text-muted small text-right mt-10"><?php echo $error['file'].':'.$error['line']; ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($error['trace'])): ?>
                    <div class="well well-sm" tabindex="-1" style="<?php echo (empty($inline)) ? '' : 'max-height: 100px; overflow: scroll;'; ?>" onclick="this.focus();">
                        <?php
                        if (is_array($error['trace']) && !empty($error['trace'])):
                            $traces = $error['trace'];
                            include __DIR__.'/app/bundles/CoreBundle/Views/Exception/traces.html.php';
                        else:
                            echo '<pre>'.$error['trace'].'</pre>';
                        endif;
                        ?>
                        <div class="clearfix"></div>
                    </div>
                <?php endif; ?>
                <div id="previous"></div>
            </div>
        </div>
        <?php elseif ($inline): ?>
        <div class="col-xs-12">
            <h3><i class="fa fa-warning fa-fw text-danger"></i><?php echo $message; ?></h3>
            <?php if (!empty($submessage)): ?>
            <h4 class="mt-15"><?php echo $submessage; ?></h4>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="col-sm-offset-3 col-sm-6">
            <div class="bg-white pa-lg text-center" style="margin-top:100px;">
                <i class="fa fa-warning fa-5x"></i>
                <h2><?php echo $message; ?></h2>
                <?php if (!empty($submessage)): ?>
                <h4 class="mt-15"><?php echo $submessage; ?></h4>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php if (empty($inline)): ?>
    </div>
</body>
</html>
<?php endif; ?>