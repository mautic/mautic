<div class="modal fade" id="campaignEventDeleteModal" tabindex="-1" role="dialog" aria-labelledby="campaignEventDeleteModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="campaignEventDeleteModalLabel"><?php echo $view['translator']->trans('mautic.campaign.event.delete'); ?></h4>
            </div>
            <div class="modal-body">
                <form id="campaignEventDeleteForm">
                    <div class="form-group">
                        <label for="campaignEventDeleteRedirect" id="campaignEventDeleteRedirectLabel"><?php echo $view['translator']->trans('mautic.campaign.event.delete.redirect_description'); ?></label>
                        <select class="form-control required" id="campaignEventDeleteRedirect">
                            <option value=""><?php echo $view['translator']->trans('mautic.campaign.event.delete.do_not_redirect'); ?></option>
                        </select>
                    </div>
                    <input type="hidden" id="campaignEventDeleteUrl" value="">
                    <input type="hidden" id="campaignEventDeleteTarget" value="">
                    <input type="hidden" id="campaignEventDeleteCampaignId" value="">

                    <div id="campaignEventDeleteWarning" class="alert alert-warning mt-2" style="display: none; margin-top: 15px;">
                        <span class="warning-message"></span><br/><br/>
                        <button type="button" class="btn btn-danger btn-sm" id="campaignEventDeleteFinalConfirm"><?php echo $view['translator']->trans('mautic.core.form.confirm'); ?></button>
                        <button type="button" class="btn btn-default btn-sm" id="campaignEventDeleteCancel"><?php echo $view['translator']->trans('mautic.core.form.cancel'); ?></button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $view['translator']->trans('mautic.core.form.cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="campaignEventDeleteConfirm"><?php echo $view['translator']->trans('mautic.campaign.event.delete.redirect_event'); ?></button>
            </div>
        </div>
    </div>
</div>
