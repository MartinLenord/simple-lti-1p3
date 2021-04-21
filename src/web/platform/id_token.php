<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use \Firebase\JWT\JWT;
$message_jwt = [
    "iss" => 'http://localhost:9001',
    // "aud" => ['d42df408-70f5-4b60-8274-6c98d3b9468d'],
    "aud" => 'd42df408-70f5-4b60-8274-6c98d3b9468d',
    "sub" => '0ae836b9-7fc9-4060-006f-27b2066ac545',
    "exp" => time() + 1200,
    "iat" => time(),
    "name" => "Trudie Senaida",
    "email" => "mlenord@turnitin.com",
    "nonce" => uniqid("nonce"),
    "https://purl.imsglobal.org/spec/lti/claim/deployment_id" => '8c49a5fa-f955-405e-865f-3d7e959e809f',
    "https://purl.imsglobal.org/spec/lti/claim/message_type" => "LtiResourceLinkRequest",
    "https://purl.imsglobal.org/spec/lti/claim/version" => "1.3.0",
    "https://purl.imsglobal.org/spec/lti/claim/target_link_uri" => "http://my.tool/app.php",
    "https://purl.imsglobal.org/spec/lti/claim/roles" => [
        "http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"
    ],
    "https://purl.imsglobal.org/spec/lti/claim/resource_link" => [
        "id" => "7b3c5109-b402-4eac-8f61-bdafa301cbb4",
    ],
    "https://purl.imsglobal.org/spec/lti/claim/context" => [
        "id" => "97d2804a-2b5f-46a1-962c-18543fd9eef6",
    ],
    "https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice" => [
        "context_memberships_url" => "http://localhost/platform/services/nrps",
        "service_versions" => ["2.0"]
    ],
];
if ($_REQUEST['lti_message_hint'] == 'subreview') {
    $message_jwt["https://purl.imsglobal.org/spec/lti/claim/message_type"] = 'LtiSubmissionReviewRequest';
    $message_jwt["https://purl.imsglobal.org/spec/lti/claim/for_user"] = [
        "id" => "0ae836b9-7fc9-4060-006f-27b2066ac545",
        "person_sourcedid" => "example.edu:71ee7e42-f6d2-414a-80db-b69ac2defd4",
        "name" => "Trudie Senaida",
        "roles" => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"]
    ];
}

$dbconn = pg_connect("host=db dbname=postgres user=postgres password=postgres");
$key_result = pg_query_params(
    $dbconn,
    'SELECT * FROM lti_key WHERE key_set_id = $1',
    ['d48a53de-021f-46f7-a0a4-7134812c2235']
);

if (!$key_result) {
    return [];
}

$key = pg_fetch_assoc($key_result);

$jwt = JWT::encode(
    $message_jwt,
    $key['private_key'],
    'RS256',
    $key['id']
);
?>
