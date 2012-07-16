<?php
ignore_user_abort(true);
set_time_limit(0);

$userCommand = urldecode($_SERVER['QUERY_STRING']);
$userCommand = escapeshellcmd($userCommand);

if (!empty($userCommand)) {
    $options = array(
        'git' => 'git',
        'dir' => '.',
    );

    if(file_exists($file = __DIR__ . '/git-config.php')) {
        $userOptions = include $file;
        $options = array_merge($options, $userOptions);
    }

    $git = $options['git'];
    $dir = $options['dir'];
    $command = "cd $dir && $git $userCommand";

    $descriptorspec = array(
        0 => array("pipe", "r"), // stdin - read channel
        1 => array("pipe", "w"), // stdout - write channel
        2 => array("pipe", "w"), // stdout - for errors
    );

    $process = proc_open($command, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        die("Can't open resource with proc_open.");
    }

    // Dont write any:
    //fwrite($pipes[0], '');
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // Close all pupes before proc_close!
    $return_value = proc_close($process);

    header("Content-Type: text/plain");
    echo $output;
    echo $error;

} else {

}
