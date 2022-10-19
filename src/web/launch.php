<?

include_once '../vendor/autoload.php';

?>
<html>
<head>
    <link href="/static/cookiecrush.css" rel="stylesheet">
    <style>
    @font-face {
        font-family: 'Gugi';
        src: url('/static/Gugi-Regular.ttf') format('truetype');
    }
    </style>
    <script type="text/javascript" src="/static/cookiecrush.js" charset="utf-8"></script>
    <script src="/static/ltistorage.js"></script>
    <script>
        !function() {
            let id_token = <?= json_encode($_POST['id_token']); ?>;
            let state = <?= json_encode($_POST['state']); ?>;

            // Send id_token to be validated
            fetch('/api/launch.php', {
                method: 'post',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    id_token: id_token,
                    state: state
                }),
            })
            // Parse the response
            .then((resp) => {
                return resp.ok ? resp.json() : Promise.reject(resp.json());
            })
            // Check Errors
            .then((resp) => {
                return resp.error_code ? Promise.reject(resp) : resp;
            })
            // Check state & nonce
            .then((resp) => {
                let storage = new LtiStorage(true);
                return storage.validateStateAndNonce(state, resp.id_token.nonce, new URL(resp.platform_origin))
                .then((ok) => {
                    return ok ? resp : Promise.reject({message:'Failed to verify state or nonce'});
                });
            })
            // ## 2 ##
            // .then((resp) => {
            //     if (document.cookie.split('; ').includes('lti_state_' + state + '=' + state)
            //         && document.cookie.split('; ').includes('lti_nonce_' + resp.id_token.nonce + '=' + resp.id_token.nonce)) {
            //         // Found state & nonce in cookie
            //         return resp;
            //     }
            //     throw {message:'Failed to verify state or nonce', 'return': resp.id_token['https://purl.imsglobal.org/spec/lti/claim/launch_presentation'].return_url };
            // })
            // ## 2 ## END

            // All good, start the game
            .then((resp) => {
                window.game(resp.id_token.name);
            })
            // Alert error
            .catch((err) => {
                if (err.return) {
                    window.location.href = err.return + '&lti_errormsg='+(err.message || err);
                } else {
                    alert(err.message || err);
                }
                throw err;
            });

        }();
    </script>
</head>
<body>
    <div id="game-screen">
        <div style="position:absolute;width:1000px;margin-left:-500px;left:50%; display:block">
            <div id="scoreboard" style="position:absolute; right:0; width:200px; height:486px">
                <h2 style="margin-left:12px;">Scoreboard</h2>
                <div id="score"></div>
                <div id="fps"></div>
                <table id="leadertable" style="margin-left:12px;">
                </table>
            </div>
            <canvas id="breakoutbg" width="800" height="500" style="position:absolute;left:0;border:0;">
            </canvas>
            <canvas id="breakout" width="800" height="500" style="position:absolute;left:0;">
            </canvas>
        </div>
    </div>

</body>
</html>