window.fbAsyncInit = function() {
FB.init({
appId      : '<?php echo $apiKeys['messenger_app_id']; ?>',
xfbml      : true,
version    : 'v2.6'
});

FB.Event.subscribe('messenger_checkbox', function(e) {
console.log("messenger_checkbox event");
console.log(e);

if (e.event == 'rendered') {
console.log("Plugin was rendered");
} else if (e.event == 'checkbox') {
var checkboxState = e.state;
console.log("Checkbox state: " + checkboxState);
console.log('test');
setTimeout(function(){    confirmOptIn(); }, 1000);
} else if (e.event == 'not_you') {
console.log("User clicked 'not you'");
} else if (e.event == 'hidden') {
console.log("Plugin was hidden");
}

});
};

function confirmOptIn() {
FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
'app_id':'<?php echo $apiKeys['messenger_app_id']; ?>',
'page_id':'<?php echo $apiKeys['messenger_page_id']; ?>',
'user_ref':'<?php echo $userRef; ?>'
});
console.log(<?php echo $userRef; ?>);
}

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
elems[i].innerHTML = '<div class="fb-messenger-checkbox" origin="<?php echo $view['assets']->getBaseUrl(); ?>" page_id="<?php echo $apiKeys['messenger_page_id']; ?>" messenger_app_id="<?php echo $apiKeys['messenger_app_id']; ?>" user_ref="<?php echo rand().time().rand(); ?>" prechecked="true" allow_login="true" size="large"></div>';
};

