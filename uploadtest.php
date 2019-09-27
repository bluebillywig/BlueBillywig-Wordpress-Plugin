<?php

require_once dirname( __FILE__ ) . '/lib/HOTP.php';
require_once dirname( __FILE__ ) . '/lib/HOTPResult.php';
require_once dirname( __FILE__ ) . '/lib/RPC.php';
require_once dirname( __FILE__ ) . '/lib/VMSUtil.php';

use BlueBillywig\VMSRPC\RPC;
$token = '219-b84f511d1ff0bf7578f1294b667f0551';
$vms = new RPC('https://bb.dev.bbvms.com', null, null, $token);
 

$properties = array('srcid'=>'test-2313425536.mxf','xml' => '<media-clip title="New mediaclip" status="published" sourceid="test-2313425536.mxf"></media-clip>');
$result = $vms->doAction('mediaclip', 'put', $properties);
 
print_r( $result );
?>