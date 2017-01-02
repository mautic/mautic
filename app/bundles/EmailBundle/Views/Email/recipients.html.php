<form novalidate="" autocomplete="false" data-toggle="ajax" role="form" name="email" method="post" action="<?php echo $action; ?>"> 
    <div class="row">
        <input type="hidden" required="true" name="objectId" value="<?php echo $user_id; ?>">

        <div class="form-group col-xs-10 ">
            <label class="control-label" >Recipients</label>
            <input type="email" required="true" name="emails[]" value="<?php echo $email; ?>" class="form-control" autocomplete="false">
        </div>  

        <div class="dynamic-field-outer"></div>   
        
        <div class="form-group col-xs-12">
            <button type="button" onclick="Mautic.addNewRecepient()" class="btn btn-primary"> Add New Recipient </button>
        </div> 
    </div>      

    <div id="lead_buttons" class="bottom-form-buttons hide">
        <button type="submit" id="email_buttons_send"  class="btn btn-default btn-save">
            <i class="fa fa-send "></i> Send
        </button>    
    </div>
</form>
