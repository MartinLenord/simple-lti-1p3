<?

include_once '../vendor/autoload.php';

use phpseclib\Crypt\RSA;
use \Firebase\JWT\JWT;

/*
    Find keyset in database
*/

$dbconn = pg_connect("host=db dbname=postgres user=postgres password=postgres");
$key_result = pg_query_params(
    $dbconn,
    'SELECT * FROM lti_key WHERE key_set_id = $1',
    ['d48a53de-021f-46f7-a0a4-7134812c2235']
);

if (!$key_result) {
    return [];
}

$keys = [];
while($key = pg_fetch_assoc($key_result)) {
    $keys[$key['id']] = $key['private_key'];
}

/*
    Convert each key to JWK format
*/

$jwks = [];
foreach ($keys as $kid => $private_key) {
    $key = new RSA();
    $key->setHash("sha256");
    $key->loadKey($private_key);
    $key->setPublicKey(false, RSA::PUBLIC_FORMAT_PKCS8);
    if ( !$key->publicExponent ) {
        continue;
    }
    $components = array(
        'kty' => 'RSA',
        'alg' => 'RS256',
        'use' => 'sig',
        'e' => JWT::urlsafeB64Encode($key->publicExponent->toBytes()),
        'n' => JWT::urlsafeB64Encode($key->modulus->toBytes()),
        'kid' => $kid,
    );
    $jwks[] = $components;
}

/*
    Output Keyset as JSON
*/

echo json_encode(['keys' => $jwks]);