<?php
ignore_user_abort(true);
set_time_limit(0);
$git = 'git';
$output = shell_exec("cd ./ && $git pull");
echo "<pre>$output</pre>";