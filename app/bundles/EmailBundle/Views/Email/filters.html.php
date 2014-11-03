<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <form action="" id="email-filters">
                <a href="#" class="btn btn-primary btn-block">
                <i class="fa fa-power-off"></i> 
                <?php echo $view['translator']->trans('mautic.email.filter.clear'); ?>
                </a>
                <hr />
                <?php if (isset($filters) && $filters) : ?>
                    <?php foreach ($filters as $filter) : ?>
                        <?php if (isset($filter['name']) && $filter['name']) : ?>
                        <h5 class="pb-10"><?php echo $view['translator']->trans($filter['name']); ?></h5>
                        <?php endif; ?>
                        <?php if (isset($filter['items']) && $filter['items']) : ?>
                        <div class="list-group custom-primary">
                            <?php foreach ($filter['items'] as $item) : ?>
                                <?php if (isset($item['title']) && $item['title']) : ?>
                                <label class="col-sm-12 list-group-item">
                                    <input 
                                        name="emailFilters[<?php echo $filter['column']; ?>][]"
                                        type="checkbox"
                                        value="<?php echo $item['id']; ?>"
                                        <?php //echo in_array($typeKey, $eventFilter) ? 'checked' : ''; ?> />
                                    <span class="mr-0"></span> 
                                    <?php echo $item['title']; ?>
                                    <i class="fa fa-square mr5 pull-right" <?php echo (isset($item['color']) && $item['color']) ? 'style="color:' . $item['color'] . '"' : ''; ?>></i>
                                </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>