<?

/*
    Initiation or Launch?
*/

if (empty($_POST['id_token'])) {
    /*
        Find Registration
    */

    $dbconn = pg_connect("host=db dbname=postgres user=postgres password=postgres");

    $result = pg_query_params(
        $dbconn,
        'SELECT * FROM lti_registration WHERE issuer = $1 AND id = $2 LIMIT 1',
        [$_REQUEST['iss'], substr($_SERVER['PATH_INFO'], 1)]
    );

    if (!$result) {
        echo 'Reg not found';
        die;
    }

    $registration = pg_fetch_assoc($result);

    if (empty($registration)) {
        echo 'Reg not found 2';
        die;
    }

    /*
        Generate state
    */

    $state = str_replace('.', '_', uniqid('state-'.sha1($_REQUEST['iss']), true));


    /*
        Build Response
    */
    $use_post_message = $_REQUEST['response_mode'] && $_REQUEST['response_mode'] == 'ims_web_message';

    $auth_params = [
        'scope'            => 'openid', // OIDC Scope.
        'response_type'    => 'id_token', // OIDC response is always an id token.
        'response_mode'    => $use_post_message ? 'ims_web_message' : 'form_post', // OIDC response is always a form post.
        'prompt'           => 'none', // Don't prompt user on redirect.
        'client_id'        => $registration['client_id'], // Registered client id.
        'redirect_uri'     => 'http://my.tool:9001/app.php', // URL to return to after login.
        'state'            => $state, // State to identify browser session.
        'nonce'            => uniqid('nonce-', true), // Prevent replay attacks.
        'login_hint'       => $_REQUEST['login_hint'], // Login hint to identify platform session.
        'lti_message_hint' => $_REQUEST['lti_message_hint'] // Login hint to identify platform session.
    ];

    if (!$use_post_message) {
        setcookie("lti1p3_$state", $state, [
            'expires' => time() + 60,
            'samesite' => 'None',
            'secure' => true,
            'path' => '/'
        ]);

        /*
            Redirect back to platform
        */
        header('Location: ' . $registration['platform_login_auth_endpoint']. '?' . http_build_query($auth_params), true, 302);
        die;
    }
    ?>
    <script>

    // Get data about the registration linked to the request
    let return_url = new URL(<?= json_encode($registration['platform_login_auth_endpoint'], JSON_UNESCAPED_SLASHES); ?>);

    // Get the parent window or opener
    let message_window = window.opener || window.parent;

    // Build data to send in web message
    let data = <?= json_encode($auth_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>

    // Listen for response containing the id_token from the platform
    window.addEventListener("message", function(event) {
        console.log(window.location.origin + " Got post message from " + event.origin);

        // Origin MUST be the same as the registered oauth return url origin
        if (event.origin !== return_url.origin) {
            console.log('invalid origin');
            return;
        }

        // Check state matches the one sent to the platform
        if (data.state !== event.data.state) {
            console.log('invalid state');
            return;
        }

        // Call server to validate id_token
        validate_lti_launch(event.data.id_token);

    }, false);

    // Send post message to platform window with login initiation data
    console.log(window.location.origin + " Sending post message to " + return_url.origin);
    console.log(data);
    message_window.postMessage(data, return_url.origin);
    </script>
    <?
}

?>
<script>
async function validate_lti_launch(id_token) {
    const form_data = new FormData();
    form_data.append('id_token', id_token);
    return fetch('/api/launch.php', {
        method: 'POST',
        body: form_data,
    })
    .then(response => response.json())
    .then(launch_response => {
        document.getElementById('id_token_code').innerText = JSON.stringify(launch_response.id_token_body, null, '    ');
        document.getElementById('memberships_code').innerText = JSON.stringify(launch_response.members, null, '    ');
        try {
            document.getElementById('dl_form').action = launch_response.id_token_body['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings'].deep_link_return_url;
            document.getElementById('dl_jwt').value = launch_response.deep_link_jwt;
        } catch (e) {}
    });
}
</script>

<h2>id_token</h2>
<code id="id_token_code" style="width:700px;height:700px;white-space:pre"></code>
<?

?>
<form id="dl_form" method="POST" action="" style="display:none">
    <input id="dl_jwt" type="hidden" name="JWT" value="" />
    <input type="submit" value="Do Deep Link"/>
</form>
<?


?>
<h2>Names and Roles</h2>
<code id="memberships_code" style="width:700px;height:700px;white-space:pre"><?
echo json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?></code>
<?

if (!empty($_POST['id_token'])) {
    ?>
    <script>
        // Do call to launch api endpoint.
        validate_lti_launch(<?= json_encode($_POST['id_token'], JSON_UNESCAPED_SLASHES); ?>);
    </script>
    <?
}

die;

?>