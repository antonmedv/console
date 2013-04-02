<?php
/**
 * Console
 *
 * @author Anton Medvedev <anton@elfet.ru>
 * @link https://github.com/elfet/console
 * @license Licensed under the MIT license.
 * @version 1.0
 */

// Change next variables as you need.

$commands = array(
    '*' => '$1',
);

// Start with this dir
$currentDir = __DIR__;
$allowChangeDir = true;

// Allowed and denied commands.
$allow = array();
$deny = array();

// Next comes the code...

/**
 * Controller
 */

// Use next two for long time executing commands.
ignore_user_abort(true);
set_time_limit(0);

// If we have a user command execute it.
// Otherwise send user interface.
if (isset($_GET['command'])) {
    $userCommand = urldecode($_GET['command']);
    $userCommand = escapeshellcmd($userCommand);
} else {
    $userCommand = false;
}

// If can - get current dir.
if ($allowChangeDir && isset($_GET['cd'])) {
    $newDir = urldecode($_GET['cd']);
    if (is_dir($newDir)) {
        $currentDir = $newDir;
    }
}

if (is_readable($file = __DIR__ . '/console.config.php')) {
    include $file;
}

// Choose action if we have user command in query - execute it.
// Else send to user html frontend of console.
if (false !== $userCommand) {
    // Check command by allow list.
    if (!empty($allow)) {
        if (!searchCommand($userCommand, $allow)) {
            $these = implode('<br>', $allow);
            die("<span class='error'>Sorry, but this command not allowed. Try these:<br>{$these}</span><br>");
        }
    }

    // Check command by deny list.
    if (!empty($deny)) {
        if (searchCommand($userCommand, $deny)) {
            die("<span class='error'>Sorry, but this command is denied.</span><br>");
        }
    }

    // Change current dir.
    if ($allowChangeDir && 1 === preg_match('/^cd\s+(?<path>.+?)$/i', $userCommand, $matches)) {
        $newDir = $matches['path'];
        $newDir = '/' === $newDir[0] ? $newDir : $currentDir . '/' . $newDir;
        if (is_dir($newDir)) {
            $newDir = realpath($newDir);
            // Interface will recognize this and save as current dir.
            die("set current directory $newDir");
        } else {
            die("<span class='error'>cd: $newDir: No such directory.</span><br>");
        }
    }

    // Check if command is not in commands list.
    if (!searchCommand($userCommand, $commands, $command, false)) {
        $these = implode('<br>', array_keys($commands));
        die("<span class='error'>Sorry, but this command not allowed. Try these:<br>{$these}</span><br>");
    }

    // Create final command and execute it.
    $command = "cd $currentDir && $command";
    list($output, $error, $code) = executeCommand($command);

    header("Content-Type: text/plain");
    echo formatOutput($userCommand, htmlspecialchars($output));
    echo htmlspecialchars($error);

    exit(0); // Terminate app
} else {
    // Send frontend to user.

    // Show current dir name.
    $currentDirName = explode('/', $currentDir);
    $currentDirName = end($currentDirName);

    // Show current user.
    list($currentUser) = executeCommand('whoami');
    $currentUser = trim($currentUser);
}

/*
 * Functions
 */

function searchCommand($userCommand, array $commands, &$found = false, $inValues = true)
{
    foreach ($commands as $key => $value) {
        list($pattern, $format) = $inValues ? array($value, '$1') : array($key, $value);
        $pattern = '/^' . str_replace('\*', '(.*?)', preg_quote($pattern)) . '$/i';
        if (preg_match($pattern, $userCommand)) {
            if (false !== $found) {
                $found = preg_replace($pattern, $format, $userCommand);
            }
            return true;
        }
    }
    return false;
}

function executeCommand($command)
{
    $descriptors = array(
        0 => array("pipe", "r"), // stdin - read channel
        1 => array("pipe", "w"), // stdout - write channel
        2 => array("pipe", "w"), // stdout - error channel
        3 => array("pipe", "r"), // stdin - This is the pipe we can feed the password into
    );

    $process = proc_open($command, $descriptors, $pipes);

    if (!is_resource($process)) {
        die("Can't open resource with proc_open.");
    }

    // Nothing to push to input.
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // TODO: Write passphrase in pipes[3].
    fclose($pipes[3]);

    // Close all pipes before proc_close!
    $code = proc_close($process);

    return array($output, $error, $code);
}

function formatOutput($command, $output)
{
    if (preg_match("%^(git )?diff%is", $command) || preg_match("%^status.*?-.*?v%is", $command)) {
        $output = formatDiff($output);
    }
    $output = formatHelp($output);
    return $output;
}

function formatDiff($output)
{
    $lines = explode("\n", $output);
    foreach ($lines as $key => $line) {
        if (strpos($line, "-") === 0) {
            $lines[$key] = '<span class="diff-deleted">' . $line . '</span>';
        }

        if (strpos($line, "+") === 0) {
            $lines[$key] = '<span class="diff-added">' . $line . '</span>';
        }

        if (preg_match("%^@@.*?@@%is", $line)) {
            $lines[$key] = '<span class="diff-sub-header">' . $line . '</span>';
        }

        if (preg_match("%^index\s[^.]*?\.\.\S*?\s\S*?%is", $line) || preg_match("%^diff.*?a.*?b%is", $line)) {
            $lines[$key] = '<span class="diff-header">' . $line . '</span>';
        }
    }

    return implode("\n", $lines);
}

function formatHelp($output)
{
    // Underline words with _0x08* symbols.
    $output = preg_replace('/_[\b](.)/is', "<u>$1</u>", $output);
    // Highlight backslash words with *0x08* symbols.
    $output = preg_replace('/.[\b](.)/is', "<strong>$1</strong>", $output);
    return $output;
}

/*
 * Autocomplete
 */

$autocomplete = array(
    '^\w*$' => array('cd', 'ls', 'mkdir', 'chmod', 'diff', 'rm', 'mv', 'cp', 'more', 'grep', 'ff', 'whoami', 'kill'),
    '^git \w*$' => array('status', 'push', 'pull', 'add', 'bisect', 'branch', 'checkout', 'clone', 'commit', 'diff', 'fetch', 'grep', 'init', 'log', 'merge', 'mv', 'rebase', 'reset', 'rm', 'show', 'tag', 'remote'),
    '^git \w* .*' => array('HEAD', 'origin', 'master', 'production', 'develop', 'rename', '--cached', '--global', '--local', '--merged', '--no-merged', '--amend', '--tags', '--no-hardlinks', '--shared', '--reference', '--quiet', '--no-checkout', '--bare', '--mirror', '--origin', '--upload-pack', '--template=', '--depth', '--help'),
);

/*
 * View
 */
?>

<!doctype html>
<html>
<head>
<title>console</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style type="text/css">
    * {
        margin: 0;
        padding: 0;
    }

    body {
        padding: 10px;
    }

    form {
        display: table;
        width: 100%;
        white-space: nowrap;
    }

    form div {
        display: table-cell;
        width: auto;
    }

    form #command {
        width: 100%;
    }

    input {
        border: none;
        outline: none;
        background: transparent;
        width: 100%;
    }

    input:focus {
        outline: none;
    }

    pre,
    form,
    input {
        color: #333333;
        font-family: 'Lucida Console', Monaco, monospace;
        font-size: 16px;
    }

    pre {
        white-space: pre;
    }

    span {
        color: blue;
    }

    strong {
        font-weight: bolder;
        font-family: Tahoma, Geneva, sans-serif
    }

    .error {
        color: red;
    }

    .autocomplete .guess {
        color: #a9a9a9;
    }

    .diff-header {
        color: #333;
        font-weight: bold;
    }

    .diff-sub-header {
        color: #33a;
    }

    .diff-added {
        color: #3a3;
    }

    .diff-deleted {
        color: #a33;
    }
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
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
                    return false;
                } else if (code == 40) { // Down
                    input.val(getCommand(--position));
                    return false;
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
     * Autocomplete input.
     */
    (function ($) {
        $.fn.autocomplete = function (suggest) {
            // Wrap and extra html to input.
            var input = $(this);
            input.wrap('<span class="autocomplete" style="position: relative;"></span>');
            var html =
                '<span class="overflow" style="position: absolute; z-index: -10;">' +
                    '<span class="repeat" style="opacity: 0;"></span>' +
                    '<span class="guess"></span></span>';
            $('.autocomplete').prepend(html);

            // Search of input changes.
            var repeat = $('.repeat');
            var guess = $('.guess');
            var search = function (command) {
                var array = [];
                for (var key in suggest) {
                    if (!suggest.hasOwnProperty(key))
                        continue;
                    var pattern = new RegExp(key);
                    if (command.match(pattern)) {
                        array = suggest[key];
                    }
                }

                var text = command.split(' ').pop();

                var found = '';
                if (text != '') {
                    for (var i = 0; i < array.length; i++) {
                        var value = array[i];
                        if (value.length > text.length &&
                            value.substring(0, text.length) == text) {
                            found = value.substring(text.length, value.length);
                            break;
                        }
                    }
                }
                guess.text(found);
            };
            var update = function () {
                var command = input.val();
                repeat.text(command);
                search(command);
            };
            input.change(update);
            input.keyup(update);
            input.keypress(update);
            input.keydown(update);

            input.keydown(function (e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (code == 9) {
                    var val = input.val();
                    input.val(val + guess.text());
                    return false;
                }
            });

            return input;
        };
    })(jQuery);

    /**
     * Windows variables.
     */
    window.currentDir = '<?php echo $currentDirName; ?>';
    window.currentDirName = window.currentDir.split('/').pop();
    window.currentUser = '<?php echo $currentUser; ?>';
    window.titlePattern = "* — console";
    window.document.title = window.titlePattern.replace('*', window.currentDirName);

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
        input.autocomplete(<?php echo json_encode($autocomplete); ?>);
        form.submit(function () {
            var command = $.trim(input.val());
            if (command == '') {
                return false;
            }

            $("<span>" + window.currentDirName + "&nbsp;" + window.currentUser + "$&nbsp;" + command + "</span><br>")
                .appendTo(screen);
            scroll();
            input.val('');
            form.hide();
            input.addHistory(command);

            $.get('', {'command': command, 'cd': window.currentDir}, function (output) {
                var pattern = /^set current directory (.+?)$/i;
                if (matches = output.match(pattern)) {
                    window.currentDir = matches[1];
                    window.currentDirName = window.currentDir.split('/').pop();
                    $('#currentDirName').text(window.currentDirName);
                    window.document.title = window.titlePattern.replace('*', window.currentDirName);
                } else {
                    screen.append(output);
                }

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
    <div id="currentDirName"><?php echo $currentDirName; ?></div>
    <div>&nbsp;<?php echo $currentUser; ?>$&nbsp;</div>
    <div id="command"><input type="text" value=""></div>
</form>
</body>
</html>