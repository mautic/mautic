Mautic
======

This is a quick "how-to" get Mautic up and running for development.

Note: a minimum of PHP 5.3.7 is required.

### Checkout Mautic

```
#!bash

git clone https://github.com:mautic/mautic.git
```

### CD into the root folder

```
#!bash

cd /Users/myuser/Sites/mautic/
```

### Install composer (if needed)

```
#!bash

curl -s https://getcomposer.org/installer | php
```
### Install vendors

```
#!bash

php composer.phar install
```

### Run the installer

Browse to http://mysite.com/index_dev.php/installer and follow the prompts.