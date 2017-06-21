<script type="text/javascript" src="<?php echo $view['router']->url('messenger_checkbox_plugin_js'); ?>"></script>
<div class="fb-messenger-checkbox"
     origin="https://bb5aa453.ngrok.io"
     page_id="<?php echo $featureSettings['messenger_page_id']; ?>"
     messenger_app_id='<?php echo $featureSettings['messenger_app_id']; ?>'
     user_ref="<?php echo $userRef; ?>"
     prechecked="true"
     allow_login="true"
     size="large"></div>