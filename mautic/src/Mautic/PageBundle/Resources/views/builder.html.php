<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(':Templates/'.$template.':page.html.php');

$head = $view['slots']->get('head', '');
$view['slots']->start('head');
echo $head;
?>

<?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
<script src="<?php echo $view->escape($url) ?>"></script>
<?php endforeach; ?>
<script src="<?php echo $view['assets']->getUrl('media/ckeditor/ckeditor.js'); ?>"></script>

<script>
    mQuery(document).ready( function() {
        var mauticAjaxUrl = '<?php echo $view['router']->generate("mautic_core_ajax"); ?>';
        CKEDITOR.disableAutoInline = true;
        mQuery("div[contenteditable='true']").each(function (index) {
            var content_id = mQuery(this).attr('id');
            CKEDITOR.inline(content_id, {
                on: {
                    blur: function (event) {
                        var data = event.editor.getData();

                        var request = mQuery.ajax({
                            url: mauticAjaxUrl + '?action=page:setBuilderContent',
                            type: "POST",
                            data: {
                                content: data,
                                slot:    content_id.replace("slot-", ""),
                                page:    mQuery('#mauticPageId').val()
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
</script>
<style>
    .mautic-editable { min-height: 75px; width: 100%; border: dashed 1px #000; margin-top: 3px; margin-bottom: 3px; }
    .mautic-content-placeholder { height: 100%; width: 100%; text-align: center; margin-top: 25px; }
</style>
<?php
$view['slots']->stop();

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    if (empty($value))
        $value = "<div class='mautic-content-placeholder'>" . $view['translator']->trans('mautic.page.page.builder.addcontent') . '</div>';
    $view['slots']->set($slot, "<div id='slot-".$slot."' class='mautic-editable' contenteditable=true>".$value."</div>");
}

//add builder toolbar
$view['slots']->start('builder');?>
<input type="hidden" id="mauticPageId" value="<?php echo $page->getSessionId(); ?>" />
<?php
$view['slots']->stop();
?>