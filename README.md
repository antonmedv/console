PHP Console for GIT
===================
Simple copy git.php under you git directory and open it in browser.

![alt screen](http://habrastorage.org/storage2/09d/b78/dff/09db78dff6aeaed69c88ec17191f369f.png)

Requrements
-----------
PHP must by able to use function "proc_open" and work from git user.


Configuration
-------------
You can run git.php from another folder by using configuration file.

Create git-config.php file with next structure:
```php
<?php
return array(
    'git' => '/home/git/bin/git',  # Another path to git bin. Default: "git"
    'dir' => '/home/var/project/', # Path to project. Default: "."
    'allow' => array(
            # List of allowed commands. Default: null (not used)
            # You can use * for any symbol. Example: "branch *" will allow "branch -v" command and ext.
        ),
    'deny' => array(
            # List of denied commands. Default: null (not used)
            # You can use * for any symbol. Example: "remote *" will deny "remote -v" command and ext.
        ),
);
```

License
-------
Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php