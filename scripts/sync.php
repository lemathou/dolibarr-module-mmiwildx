#!/bin/php
<?php

$script_file = basename(__FILE__);

// Include Dolibarr environment
require_once __DIR__.'/../master_load.inc.php';

dol_include_once('mmiwildx/class/mmi_wildx_sync.class.php');

// agv to get
foreach ($argv as $key=>$arg) {
        if ($key==0)
                continue;
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else
        $_GET[$e[0]]=0;
}

$options = [
];

if (!empty($_GET['ym']))
	$options['ym'] = $_GET['ym'];
if (!empty($_GET['start']))
        $options['start'] = $_GET['start'];
//var_dump($_GET, $options); die();

mmi_wildx_sync::sync($options);

