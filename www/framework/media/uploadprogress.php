<?php
// don't cache
header('Expires: Tue, 08 Oct 1991 00:00:00 GMT');
header('Cache-Control: no-cache, must-revalidate');

$json['apc_enabled'] = function_exists('apc_fetch'); 

if(isset($_GET['APC_UPLOAD_PROGRESS']) && !empty($json['apc_enabled'])) {
    $status = apc_fetch('upload_' . $_GET['APC_UPLOAD_PROGRESS']);
    
    if ($status) {
        $json['state'] = "uploading";
        $json['received'] = $status['current'];
        $json['size'] = $status['total'];
    }
}

echo json_encode($json);
