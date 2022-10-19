<?

include_once '../../vendor/autoload.php';

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

JWT::$leeway = 5;

function output_json($arr) {
    echo json_encode($arr);
    die;
}

/*
    Decode token
*/
$id_token = json_decode(file_get_contents('php://input'), true)['id_token'];

if (empty($id_token)) {
    output_json(['message' => 'Missing id token', 'error_code' => 400]);
}

$id_token_parts = explode('.', $id_token);

$id_token_body = json_decode(JWT::urlsafeB64Decode($id_token_parts[1]), true);

/*
    Find registration details
*/

$dbconn = pg_connect("host=db dbname=postgres user=postgres password=postgres");

$result = pg_query_params(
    $dbconn,
    'SELECT * FROM lti_registration WHERE issuer = $1 AND client_id = $2 LIMIT 1',
    [
        $id_token_body['iss'],
        is_array($id_token_body['aud']) ? $id_token_body['aud'][0] : $id_token_body['aud']
    ]
);

if (!$result) {
    output_json(['message' => 'Registration not found', 'error_code' => 404]);
}

$registration = pg_fetch_assoc($result);

if (empty($registration)) {
    output_json(['message' => 'Registration not found', 'error_code' => 404]);
}

/*
    Find platform public key
*/

$public_key_set = json_decode(file_get_contents($registration['platform_jwks_endpoint']), true);

$id_token_header = json_decode(JWT::urlsafeB64Decode($id_token_parts[0]), true);

$public_key;
foreach ($public_key_set['keys'] as $key) {
    if ($key['kid'] == $id_token_header['kid']) {
        try {
            $public_key = openssl_pkey_get_details(JWK::parseKey($key));
            break;
        } catch(\Exception $e) {
            output_json(['message' => 'Failed to parse public key', 'error_code' => 400]);
        }
    }
}

/*
    Validate id_token Signature
*/

try {
    JWT::decode($id_token, $public_key['key'], array('RS256'));
} catch(\Exception $e) {
    output_json(['message' => 'Failed to validate signature: ' . $e, 'error_code' => 401]);
}

/*
    Print id_token content
*/

echo json_encode(['id_token' => $id_token_body, 'platform_origin' => $registration['platform_login_auth_endpoint']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
die;

/*
    Get tool private key
*/

$key_result = pg_query_params(
    $dbconn,
    'SELECT * FROM lti_key WHERE key_set_id = $1 LIMIT 1',
    [$registration['key_set_id']]
);

if (!$key_result) {
    die;
}

$tool_key = pg_fetch_assoc($key_result);

if (empty($key_result)) {
    output_json(['message' => 'Could not find the tool\'s key', 'error_code' => 400]);
}

/*
    Remove for services
*/
die;

/*
    Get an access token to make service requests
*/

$service_jwt_claim = [
    "iss" => $registration['client_id'],
    "sub" => $registration['client_id'],
    "aud" => $registration['platform_auth_provider'] ?: $registration['platform_service_auth_endpoint'],
    "iat" => time() - 5,
    "exp" => time() + 60,
    "jti" => 'lti-service-token' . hash('sha256', random_bytes(64))
];

$service_jwt = JWT::encode($service_jwt_claim, $tool_key['private_key'], 'RS256', $tool_key['id']);

$service_auth_request = [
    'grant_type' => 'client_credentials',
    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
    'client_assertion' => $service_jwt,
    'scope' => 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $registration['platform_service_auth_endpoint']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($service_auth_request));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$resp = curl_exec($ch);
$token_data = json_decode($resp, true);
curl_close ($ch);

/*
    Use access token to call Names and Roles service
*/

$members = [];

$next_page = $id_token_body['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice']['context_memberships_url'];

while ($next_page) {
    $ch = curl_init();
    $headers = [
        'Authorization: Bearer ' . $token_data['access_token'],
        'Accept: application/vnd.ims.lti-nrps.v2.membershipcontainer+json',
    ];
    curl_setopt($ch, CURLOPT_URL, $next_page);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close ($ch);

    $resp_headers = substr($response, 0, $header_size);
    $resp_body = substr($response, $header_size);
    $page = [
        'headers' => array_filter(explode("\r\n", $resp_headers)),
        'body' => json_decode($resp_body, true),
    ];
    $members = array_merge($members, $page['body']['members']);

    $next_page = false;
    foreach($page['headers'] as $header) {
        if (preg_match("/^Link:.*<([^>]*)>; ?rel=\"next\"/i", $header, $matches)) {
            $next_page = $matches[1];
            break;
        }
    }
}

/*
    Output Service Response
*/

?>
<h2>Names and Roles</h2>
<code style="width:700px;height:700px;white-space:pre"><?
echo json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?></code>
<?