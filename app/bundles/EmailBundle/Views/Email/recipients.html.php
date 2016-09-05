
	<form novalidate="" autocomplete="false" data-toggle="ajax" role="form" name="email" method="post" action="<?= $action; ?>"> 
 
        <div class="row">
		    	<div class="form-group col-xs-10 ">
		        	<label class="control-label" >Recipients</label>
		        	<input type="text" name="emails[]" class="form-control" autocomplete="false">
				</div>  

				<div class="dynamic-field-outer"></div>   
				
				<div class="form-group col-xs-12">
					<button type="button" onclick="addNewRecepient()" class="btn btn-primary"> Add New Recipient </button>
				</div> 
		</div>      

<div id="lead_buttons" class="bottom-form-buttons hide">
        <button type="submit" id="lead_buttons_cancel" name="lead[buttons][cancel]" class="btn btn-default btn-cancel">
        <i class="fa fa-times text-danger "></i>
        Cancel</button>    <button type="submit" id="lead_buttons_save" name="lead[buttons][save]" class="btn btn-default btn-save">
        <i class="fa fa-save "></i>
        Save</button>    </div>



 	</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript">
	function addNewRecepient() {
		var textBox = '';
		textBox +=	'<div class="dynamic-filed">';
		textBox +=	'<div class="form-group col-xs-10">';
		textBox +=	'<input type="text" name="emails[]" class="form-control" autocomplete="false">';
		textBox +=	'</div> ';
		textBox +=	'<div class="form-group col-xs-2">';
		textBox +=	'<button type="button" onclick="deleteRecipient(this)" class="btn btn-danger"> X </button>';
		textBox +=	'</div>  ';
		textBox +=	'</div>  ';
		$('.dynamic-field-outer').append(textBox);
	}
	function deleteRecipient(_this) {
		$(_this).closest('.dynamic-filed').remove();
	}

</script>
