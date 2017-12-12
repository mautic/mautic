window.fbAsyncInit = function() {
FB.init({
appId      : '<?php echo $apiKeys['messenger_app_id']; ?>',
xfbml      : true,
version    : 'v2.6'
});

FB.Event.subscribe('messenger_checkbox', function(e) {

if (e.event == 'rendered') {

function confirmOptIn() {
FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
'app_id':'<?php echo $apiKeys['messenger_app_id']; ?>',
'page_id':'<?php echo $apiKeys['messenger_page_id']; ?>',
'user_ref':'<?php echo $userRef; ?>',
'ref':'<?php echo $contactId; ?>'
});
}


}

});
};

(function(d, s, id){
var js, fjs = d.getElementsByTagName(s)[0];
if (d.getElementById(id)) {return;}
js = d.createElement(s); js.id = id;
js.src = "//connect.facebook.net/en_US/sdk.js";
fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk')
);

var elems = document.querySelectorAll('.messengerCheckboxPlugin');

for (var i = 0; i < elems.length; i++) {
elems[i].innerHTML = '<div class="fb-messenger-checkbox" origin="'+location.protocol + '//' + location.host+'" page_id="<?php echo $apiKeys['messenger_page_id']; ?>" messenger_app_id="<?php echo $apiKeys['messenger_app_id']; ?>" user_ref="<?php echo rand().time().rand(); ?>" prechecked="true" allow_login="true" size="large"></div>';
};


if (typeof MauticFormCallback == 'undefined') {
var MauticFormCallback = {};
}
MauticFormCallback['<?php echo str_replace('_', '', $formName); ?>'] = {
onValidateEnd: function (formValid) {
},
onResponse: function (response) {
}
};

