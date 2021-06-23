<?php
require_once __DIR__ . '/id_token.php';
?>
<script>

    // // Fake platform reg data
    // let plat_reg_data = {
    //     'd42df408-70f5-4b60-8274-6c98d3b9468d' : {
    //         'redirect_uris' : [
    //             'http://my.tool:9001/app.php'
    //         ],
    //         'oidc_login_url' : 'http://my.tool:9001/login.php'
    //     }
    // };

    let login_hint = '' + Math.floor(Math.random() * 100000);


    // window.addEventListener("message", function(event) {
    //     console.log(window.location.origin + " Got post message from " + event.origin);
    //     console.log(event);
    //     // Check login hint
    //     if (login_hint !== event.data.login_hint) {
    //         console.log('invalid login hint');
    //         console.log(event);
    //         return;
    //     }

    //     // Find registration
    //     let reg = plat_reg_data[event.data.client_id];

    //     // Get allowed redirect urls
    //     if (!reg.redirect_uris.includes(event.data.redirect_uri)) {
    //         console.log('invalid redirect url '+event.data.redirect_uri);
    //         return;
    //     }

    //     // Origin MUST be the same as the registered redirect url origin
    //     let launch_url = new URL(event.data.redirect_uri);
    //     if (event.origin !== launch_url.origin) {
    //         console.log('invalid origin');
    //         console.log(event);
    //         return;
    //     }

    //     // Post back launch to the host of the registered redirect url only
    //     let pm_data = {
    //             'id_token': '<?= $jwt ?>',
    //             'state': event.data.state
    //     };
    //     console.log(window.location.origin + " Sending post message to " + launch_url.origin);
    //     console.log(pm_data);
    //     event.source.postMessage(pm_data, launch_url.origin);
    //     let subframe = event.source.document.getElementById('subframe');
    //     subframe.postMessage(pm_data, launch_url.origin);

    // }, false);
</script>
<ul>
    <li>Fancy LMS</li>
    <li>Users</li>
    <li>Courses</li>
        <li class="sub" onclick="document.getElementById('frame').src='http://my.tool:9001/login_initiation.php/56f3d0ed-0e0a-4ba5-a5a2-59aa4bbe6b59?iss=http%3A%2F%2Flocalhost:9001&login_hint='+login_hint+'&target_link_uri=http%3A%2F%2Flocalhost%2Fgame.php&lti_message_hint=12345'">Standard OIDC</li>
        <li class="sub" onclick="document.getElementById('frame').src='http://my.tool:9001/login_initiation.php/56f3d0ed-0e0a-4ba5-a5a2-59aa4bbe6b59?iss=http%3A%2F%2Flocalhost:9001&login_hint='+login_hint+'&target_link_uri=http%3A%2F%2Flocalhost%2Fgame.php&lti_message_hint=12345&response_mode=ims_web_message&ims_web_message_target=csstorage'">Post message</li>
        <li class="sub" onclick="document.getElementById('frame').src='http://my.tool:9001/login_initiation.php/56f3d0ed-0e0a-4ba5-a5a2-59aa4bbe6b59?iss=http%3A%2F%2Flocalhost:9001&login_hint='+login_hint+'&target_link_uri=http%3A%2F%2Flocalhost%2Fgame.php&lti_message_hint=12345&response_mode=ims_web_message'">Invalid Origin</li>
    <li>Settings</li>
</ul>
<iframe id="csstorage" name="csstorage" style="border:none;" src="http://my.platform:9001/platform/csstorage.php" ></iframe>
<div class="frame-wrapper"><iframe id="frame" style="border:none;" ></iframe></div>

<style>
ul {
    position:absolute;
    left:0px;
    top:0px;
    width:200px;
    bottom:0;
    background-color:darkslategray;
    color: white;
    font-family: Verdana, Geneva, Tahoma, sans-serif;
    font-size: 28px;
    font-weight: bold;
    margin:0;
    list-style-type: none;
}
li {
    padding-top: 26px;

}
li.sub {
    padding-left:26px;
    font-size: 24px;
}
#frame {
    position: absolute;
    left:0px;
    right:0px;
    top:0px;
    bottom:0px;
    width:100%;
    height:100%;
}
.frame-wrapper {
    position: absolute;
    left: 240px;
    right:0px;
    top:0px;
    bottom:0px;
}
</style>
<? include __DIR__ . '/csstorage.php';