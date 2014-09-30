<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Mautic</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('assets/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('assets/images/apple-touch-icon.png') ?>" />

    <?php $view['assets']->outputSystemStylesheets(); ?>
</head>
<body>
<section id="main" role="main">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4">
                <div class="text-center">
                    <img src="<?php echo $view['assets']->getUrl('assets/images/mautic_logo_lb200.png') ?>" height="50px" />
                    <h5 class="semibold text-muted mt-5"><?php $view['slots']->output('header', ''); ?></h5>
                </div>
                <hr />

                <div class="panel" name="form-login">
                    <div class="panel-body">
                        <div id="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
                        </div>

                        <div class="form-group">
                            <select class="form-control" name="lang">
                                <option value="0">Select language</option>
                                <option value="en">English</option>
                                <option value="da">Danish - Dansk</option>
                                <option value="nl">Dutch - Nederlands</option>
                                <option value="en-gb">English - UK</option>
                                <option value="fr">French - français</option>
                                <option value="de">German - Deutsch</option>
                            </select>
                        </div>
                        
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    //clear typeahead caches
    window.localStorage.clear();
</script>
<?php $view['assets']->outputSystemScripts(); ?>
</body>
</html>