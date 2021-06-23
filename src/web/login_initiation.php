<?

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
    echo 'reg not found';
    die;
}

$registration = pg_fetch_assoc($result);

if (empty($registration)) {
    echo 'reg empty';
    die;
}

/*
    Store Cookie in state
*/

$state = str_replace('.', '_', uniqid('state-', true));
setcookie("lti1p3_$state", $state, [
    'expires' => time() + 60,
    'samesite' => 'None',
    'secure' => true,
    'path' => '/'
]);

/*
    Build Response
*/

$auth_params = [
    'scope'            => 'openid', // OIDC Scope.
    'response_type'    => 'id_token', // OIDC response is always an id token.
    'response_mode'    => 'form_post', // OIDC response is always a form post.
    'prompt'           => 'none', // Don't prompt user on redirect.
    'client_id'        => $registration['client_id'], // Registered client id.
    'redirect_uri'     => 'http://my.tool:9001/launch.php', // URL to return to after login.
    'state'            => $state, // State to identify browser session.
    'nonce'            => uniqid('nonce-', true), // Prevent replay attacks.
    'login_hint'       => $_REQUEST['login_hint'], // Login hint to identify platform session.
    'lti_message_hint' => $_REQUEST['lti_message_hint'] // Login hint to identify platform session.
];

/*
    Redirect back to platform
*/

// header('Location: ' . $registration['platform_login_auth_endpoint']. '?' . http_build_query($auth_params), true, 302);
// die;


?>

<script>

    // Get data about the registration linked to the request
    let return_url = new URL(<?= json_encode($registration['platform_login_auth_endpoint'], JSON_UNESCAPED_SLASHES); ?>);

    // Get the parent window or opener
    let message_window = (window.opener || window.parent)<?= isset($_REQUEST['ims_web_message_target']) ? '.frames["'.$_REQUEST['ims_web_message_target'].'"]' : ''; ?>;

    // Build data to send in web message
    let data = <?= json_encode($auth_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>

    let state_set = false;

    // Listen for response containing the id_token from the platform
    window.addEventListener("message", function(event) {
        console.log(window.location.origin + " Got post message from " + event.origin);
        console.log(JSON.stringify(event.data, null, '    '));

        // Origin MUST be the same as the registered oauth return url origin
        if (event.origin !== return_url.origin) {
            console.log('invalid origin');
            return;
        }

        // Check state matches the one sent to the platform
        if (event.data.subject !== 'org.imsglobal.lti.put_data.response' ) {
            console.log('invalid response');
            return;
        }

        state_set = true;

        window.location.href = <?= json_encode($registration['platform_login_auth_endpoint']. '?' . http_build_query($auth_params), JSON_UNESCAPED_SLASHES); ?>;


    }, false);

    // Send post message to platform window with login initiation data
    let send_data = {
        subject: 'org.imsglobal.lti.put_data',
        message_id: Math.random(),
        key: "state",
        value: "<?= $state ?>",
    };
    console.log(window.location.origin + " Sending post message to " + return_url.origin);
    console.log(JSON.stringify(send_data, null, '    '));
    message_window.postMessage(send_data, return_url.origin);
    setTimeout(() => { if (!state_set) { alert('no response from platform'); } }, 1000);
</script>