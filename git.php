<?php
ignore_user_abort(true);
set_time_limit(0);
$git = 'git';
$comand = "cd . && $git remote show origin";
$output = shell_exec($command);

$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
    1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
    2 => array("file", "/tmp/error-output.txt", "a") // stderr - файл для записи
);

$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {
    // $pipes теперь выглядит так:
    // 0 => записывающий обработчик, подключенный к дочернему stdin
    // 1 => читающий обработчик, подключенный к дочернему stdout
    // Вывод сообщений об ошибках будет добавляться в /tmp/error-output.txt

    fwrite($pipes[0], '<?php print_r($_ENV); ?>');
    fclose($pipes[0]);

    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Важно закрывать все каналы перед вызовом
    // proc_close во избежание мертвой блокировки
    $return_value = proc_close($process);

    echo "команда вернула $return_value\n";
}

echo "<pre>$output</pre>";