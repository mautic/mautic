<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", sprintf('%s: %s',$appIntegration->getAppName(),$appObject->getObjectName()));
?>
<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
        <form action="" method="post" action="" role="form">
            <div class="pa-md">
                <div class="form-group mb-0">
                    <label class="control-label mb-xs">Mautic Points</label>
                    <div class="row">
                        <div class="col-sm-4">
                            <input type="number" name="mautic_points" value="5" max="100" min="1">
                        </div>
                    </div>
                </div>
                <hr class="mnr-md mnl-md">
                <div class="form-group mb-0">
                    <table class="table table-hover table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>
                                Mautic Field
                            </th>
                            <th>
                                <?php echo $appIntegration->getAppName(); ?> Field
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($item)): ?>
                            <tr id="field_copy">
                                <td>
                                    <select name="mapper[source_field][]">
                                        <?php foreach ($sourceFields as $field): ?>
                                            <option value="<?php echo $field->getAlias(); ?>"><?php echo $field->getAlias(); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="mapper[target_field][]">
                                        <?php foreach ($targetOptions as $field): ?>
                                            <option value="<?php echo $field['value']; ?>"><?php echo $field['text']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" onclick="copyFields();" class="btn btn-primary"> Add new field </button>
                </div>
            </div>
        </form>
    </div>
</div>