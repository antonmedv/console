PHP Console for GIT
===================
Simply copy/symlink git.php into you cloned repo and open it in browser.

![alt screen](http://habrastorage.org/storage2/09d/b78/dff/09db78dff6aeaed69c88ec17191f369f.png)

Requrements
-----------
PHP must by able to use function "proc_open", also chown .git/ folder to apache user

Configuration
-------------
You can run git.php from another folder by using configuration file.

Copy git-config.php.sample into git-config.php, place it (with your settings inside) near git.php

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
