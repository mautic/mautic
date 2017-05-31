<?php
$id = mt_rand().time();;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    <script
        src="https://code.jquery.com/jquery-2.2.4.min.js"
        integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
        crossorigin="anonymous"></script>

    <script>
        function confirmOptIn() {
            FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
                'app_id':'117095855538543',
                'page_id':'1762962987252373',
                'ref':'test',
                'user_ref':'<?php echo $id; ?>'
            });
            console.log(<?php echo $id; ?>);
        }

    </script>
</head>
<body>

<h1>Messenger test</h1>
<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '117095855538543',
            xfbml      : true,
            version    : 'v2.9'
        });
        confirmOptIn();

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

    (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {return;}
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/en_US/sdk.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk')
    );
</script>

</body>


</html>