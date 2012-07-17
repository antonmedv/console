<?php
ignore_user_abort(true);
set_time_limit(0);

$userCommand = urldecode($_SERVER['QUERY_STRING']);
$userCommand = escapeshellcmd($userCommand);

if (!empty($userCommand)) {
    $options = array(
        'git' => 'git',
        'dir' => '.',
        'allow' => null,
        'deny' => null,
    );

    if (is_readable($file = __DIR__ . '/git-config.php')) {
        $userOptions = include $file;
        $options = array_merge($options, $userOptions);
    }

    if (is_array($options['allow'])) {
        if (!in_array($userCommand, $options['allow'])) {
            $these = implode('<br>', $options['allow']);
            die("<span class='error'>Sorry, but this command not allowed. Try these:<br>{$these}</span><br>");
        }
    }

    if (is_array($options['deny'])) {
        if (in_array($userCommand, $options['deny'])) {
            die("<span class='error'>Sorry, but this command is denied.</span><br>");
        }
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

    // Close all pipes before proc_close!
    $return_value = proc_close($process);

    header("Content-Type: text/plain");
    echo htmlspecialchars($output);
    echo htmlspecialchars($error);

    exit(0);
}
?>

<!doctype html>
<html>
<head>
    <title>php-git</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
        }

        body {
            padding: 10px;
        }

        form {
            white-space: nowrap;
        }

        input {
            border: none;
            outline: none;
            width: 500px;
        }

        input:focus {
            outline: none;
        }

        pre,
        form,
        input {
            color: #333333;
            font-family: ‘ Lucida Console ’, Monaco, monospace;
            font-size: 16px;
        }

        pre {
            white-space: pre;
        }

        span {
            color: blue;
        }

        span.error {
            color: red;
        }
    </style>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script type="text/javascript">
        /**
         *  History of commands.
         */
        (function ($) {
            var maxHistory = 100;
            var position = -1;
            var currentCommand = '';
            var addCommand = function (command) {
                var ls = localStorage['commands'];
                var commands = ls ? JSON.parse(ls) : [];
                if (commands.length > maxHistory) {
                    commands.shift();
                }
                commands.push(command);
                localStorage['commands'] = JSON.stringify(commands);
            };
            var getCommand = function (at) {
                var ls = localStorage['commands'];
                var commands = ls ? JSON.parse(ls) : [];
                if (at < 0) {
                    position = at = -1;
                    return currentCommand;
                }
                if (at >= commands.length) {
                    position = at = commands.length - 1;
                }
                return commands[commands.length - at - 1];
            };

            $.fn.history = function () {
                var input = $(this);
                input.keydown(function (e) {
                    var code = (e.keyCode ? e.keyCode : e.which);

                    if (code == 38) { // Up
                        if (position == -1) {
                            currentCommand = input.val();
                        }
                        input.val(getCommand(++position));
                    } else if (code == 40) { // Down
                        input.val(getCommand(--position));
                    } else {
                        position = -1;
                    }
                });

                return input;
            };

            $.fn.addHistory = function (command) {
                addCommand(command);
            };
        })(jQuery);

        /**
         * Init console.
         */
        $(function () {
            var screen = $('pre');
            var input = $('input').focus();
            var form = $('form');
            var scroll = function () {
                window.scrollTo(0, document.body.scrollHeight);
            };
            input.history();
            form.submit(function () {
                var command = $.trim(input.val());
                if (command == '') {
                    return false;
                }

                $("<span>&rsaquo; git " + command + "</span><br>").appendTo(screen);
                scroll();
                input.val('');
                form.hide();
                input.addHistory(command);

                $.get('?' + command, function (output) {
                    screen.append(output);
                    form.show();
                    scroll();
                });
                return false;
            });

            $(document).click(function () {
                input.focus();
            });
        });
    </script>
</head>
<body>
<pre></pre>
<form>
    &rsaquo; git <input type="text" value="">
</form>
</body>
</html>
