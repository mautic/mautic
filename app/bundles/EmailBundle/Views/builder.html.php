<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:email.html.php");

$view['assets']->addScriptDeclaration("var mauticBasePath = '$basePath';");

$view['assets']->addScript($view['assets']->getSystemScripts());

$view['assets']->addScript(array(
    'assets/js/ckeditor/ckeditor.js',
    'assets/js/ckeditor/adapters/jquery.js'
));

$custom = <<<CUSTOM
mQuery(document).ready( function() {
    var mauticAjaxUrl = '{$view['router']->generate("mautic_core_ajax")}';
    CKEDITOR.disableAutoInline = true;
    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        CKEDITOR.inline(content_id, {
            on: {
                blur: function (event) {
                    var data = event.editor.getData();

                    var request = mQuery.ajax({
                        url: mauticAjaxUrl + '?action=email:setBuilderContent',
                        type: "POST",
                        data: {
                            content: data,
                            slot:    content_id.replace("slot-", ""),
                            email:    mQuery('#mauticEmailId').val()
                        },
                        dataType: "html"
                    });
                },
                focus: function (event) {
                    mQuery('#' + content_id).find('.mautic-content-placeholder').remove();
                }
            }
        });
    });
});
CUSTOM;
$view['assets']->addScriptDeclaration($custom);

$css = <<<CSS
.mautic-editable { min-height: 75px; width: 100%; border: dashed 1px #000; margin-top: 3px; margin-bottom: 3px; }
.mautic-content-placeholder { height: 100%; width: 100%; text-align: center; margin-top: 25px; }
.mautic-editable.over-droppable { border: dashed 1px #ED9C28; }
CSS;
$view['assets']->addStyleDeclaration($css);

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    if (empty($value))
        $value = "<div class='mautic-content-placeholder'>" . $view['translator']->trans('mautic.email.builder.addcontent') . '</div>';
    $view['slots']->set($slot, "<div id='slot-".$slot."' class='mautic-editable' contenteditable=true>".$value."</div>");
}

//add builder toolbar
$view['slots']->start('builder');?>
<input type="hidden" id="mauticEmailId" value="<?php echo $email->getSessionId(); ?>" />
<?php
$view['slots']->stop();
?>