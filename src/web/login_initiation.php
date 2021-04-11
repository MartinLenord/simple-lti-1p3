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
    die;
}

$registration = pg_fetch_assoc($result);

if (empty($registration)) {
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
    'redirect_uri'     => 'https://lti-simple.ngrok.io/launch.php', // URL to return to after login.
    'state'            => $state, // State to identify browser session.
    'nonce'            => uniqid('nonce-', true), // Prevent replay attacks.
    'login_hint'       => $_REQUEST['login_hint'], // Login hint to identify platform session.
    'lti_message_hint' => $_REQUEST['lti_message_hint'] // Login hint to identify platform session.
];

/*
    Redirect back to platform
*/

header('Location: ' . $registration['platform_login_auth_endpoint']. '?' . http_build_query($auth_params), true, 302);
die;

?>