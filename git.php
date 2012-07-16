<?php
ignore_user_abort(true);
set_time_limit(0);
$git = '/home/u77602/git/git/bin/git';
$output = shell_exec("cd ../chat/ && $git pull");
echo "<pre>$output</pre>";