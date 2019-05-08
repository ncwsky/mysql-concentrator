<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

function write_error($msg)
{
    $stderr = fopen('php://stderr', 'w');
    fwrite($stderr, $msg);
    fclose($stderr);
}

$options = getopt("h:p:l:");
$exit_status = 0;
if (!array_key_exists('h', $options)) {
    write_error("You must provide a host parameter (-h).\n");
    $exit_status = 64;
}
if (!array_key_exists('p', $options)) {
    write_error("You must provide a port parameter (-p).\n");
    $exit_status = 64;
}
if ($exit_status == 0) {
    $settings = array(
        'host' => $options['h'],
        'port' => $options['p'],
    );
    if (array_key_exists('l', $options)) {
        $settings['listen_port'] = $options['l'];
    }
    $mysql_concentrator = new MySQLConcentrator\Server($settings);
    $mysql_concentrator->run();
}
echo $exit_status, PHP_EOL;
exit();
