# Installation

For adding WhiteLib PHP to your project type next:

```$ cd /root/of/your/git/project
$ git submodule add --name white-lib-php git@gitlab.whitecode.ru:nevmerzhitsky/white-lib-php.git vendor/white-lib-php```

Then add something like next to your bootstrap file:

```define('INCBASE', __DIR__ . '/');
define('APPBASE', INCBASE . '../');
define('VENDORBASE', APPBASE . 'vendor/');

require_once VENDORBASE . 'white-lib-php/src/init.php';```

