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
    echo 'Missing registration';
    die;
}

$registration = pg_fetch_assoc($result);

if (empty($registration)) {
    echo 'Missing registration';
    die;
}

/*
    Build Response
*/

$auth_params = [
    'scope'            => 'openid', // OIDC Scope.
    'response_type'    => 'id_token', // OIDC response is always an id token.
    'response_mode'    => 'form_post', // OIDC response is always a form post.
    'prompt'           => 'none', // Don't prompt user on redirect.
    'client_id'        => $registration['client_id'], // Registered client id.
    'redirect_uri'     => 'https://lti-simple.ngrok.io/launch.php', // URL to return to after login.
    'login_hint'       => $_REQUEST['login_hint'], // Login hint to identify platform session.
    'lti_message_hint' => $_REQUEST['lti_message_hint'] // Login hint to identify platform session.
];

?>
<script src="/static/ltistorage.js"></script>
<body></body>
<script>

    let params = <?= json_encode($auth_params) ?>;


    let storage = new LtiStorage(true);
    storage.initToolLogin(new URL(<?= json_encode($registration['platform_login_auth_endpoint']); ?>), params);

    // ## 1 ##
    // params.state = "<?= uniqid('', true) ?>";
    // params.nonce = "<?= uniqid('', true) ?>";
    // document.cookie = 'lti_state_' + params.state + '=' + params.state + '; path=/; samesite=none; secure; expires=' + (new Date(Date.now() + 300*1000)).toUTCString();
    // document.cookie = 'lti_nonce_' + params.nonce + '=' + params.nonce + '; path=/; samesite=none; secure; expires=' + (new Date(Date.now() + 300*1000)).toUTCString();


    // let form = document.createElement("form");
    // for (let key in params) {
    //     let element = document.createElement("input");
    //     element.type = 'hidden';
    //     element.value = params[key];
    //     element.name = key;
    //     form.appendChild(element);
    // };

    // form.method = "POST";
    // form.action = <?= json_encode($registration['platform_login_auth_endpoint']); ?>;

    // document.body.appendChild(form);

    // form.submit();
    // ## 1 ## END


</script>