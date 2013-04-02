Web PHP Console
================
Simply copy/symlink console.php into you www directory and open it in browser.

![alt screen](http://habrastorage.org/storage2/09d/b78/dff/09db78dff6aeaed69c88ec17191f369f.png)

Requrements
-----------
PHP must by able to use function "proc_open".
Also chown .git/ folder to apache user to use git.

Configuration
-------------
You can run console.php from another folder by using configuration file.

Copy console.config.php.example into console.config.php, place it (with your settings inside) near console.php

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
