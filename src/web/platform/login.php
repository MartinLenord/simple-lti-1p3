<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/id_token.php';

?>

<form id="auto_submit" action="<?= $_REQUEST['redirect_uri']; ?>" method="POST">
    <input type="hidden" name="id_token" value="<?= $jwt ?>" />
    <input type="hidden" name="ims_web_message_target" value="csstorage" />
    <input type="hidden" name="state" value="<?= $_REQUEST['state']; ?>" />
</form>
<script>
    document.getElementById('auto_submit').submit();
</script>
