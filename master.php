#!/usr/bin/php -q
<?php
error_reporting(E_ALL);
require_once 'Startup.php';
require_once 'Handshake.class.php';
require_once 'Specifications.classes.php';
require_once 'Node.class.php';
require_once 'Server.class.php';
require_once 'Data.class.php';

$server = new Server($address, $port);
$server->run();
?>
