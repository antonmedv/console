Web PHP Console
================
Simply copy/symlink console.php into you www directory and open it in browser.

![alt screen](http://habrastorage.org/storage2/c0c/36c/430/c0c36c43093106d7d95d4b03e8b6dcb5.png)

Features
--------
* Just one file.
* Navigation throw cd commands.
* Commands aliases and patterns.
* List of allowed commands.
* List of denied commands.
* Digest HTTP Authentication.
* Themes.
* Suggest of most commonly used commands.
* History of commands.
* Mobile support.

Requrements
-----------
PHP must be able to use function "proc_open".
Also chown .git/ folder to apache user to use git.

Configuration
-------------
You can run console.php from another folder by using configuration file.

Copy console.config.php.example into console.config.php, place it (with your settings inside) near console.php

Use from another user
---------------------
If you want use console not from `www-data` use sudo.
Set sudo to not asking passwords. Open visudo and add:
```
www-data    ALL=(user) NOPASSWD:ALL
```
If you want all commands run by sudo, change $commands like this:
```php
$commands = array(
    '*' => "sudo -u user $1",
);
```

Usefull commands aliases
------------------------
```php
$commands = array(
    'symfony*' => "sudo -u user TERM=xterm app/console$1",
    'composer*' => "sudo -u user TERM=xterm /usr/local/bin/composer$1",
    'git*' => "sudo -u user /usr/local/git/bin/git$1",
    '*' => "sudo -u user $1",
);
```

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
