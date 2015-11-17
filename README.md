# A5sys PHP CodeSniffer Coding Standard

## Installation

### Composer

This standard can be installed with the [Composer](https://getcomposer.org/) dependency manager.

1. [Install Composer](https://getcomposer.org/doc/00-intro.md)

2. Install the coding standard as a dependency of your project

        composer require --dev A5sys/A5sys-coding-standard:~2.0

3. Add the coding standard to the PHP_CodeSniffer install path

        vendor/bin/phpcs --config-set installed_paths vendor/A5sys/A5sys-coding-standard

4. Check the installed coding standards for "A5sys"

        vendor/bin/phpcs -i

5. Done!

        vendor/bin/phpcs /path/to/code

### Stand-alone

1. Install [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)

2. Checkout this repository 

        git clone git://github.com/A5sys/A5sys-coding-standard.git

3. Add the coding standard to the PHP_CodeSniffer install path

        phpcs --config-set installed_paths /path/to/A5sys-coding-standards

   Or copy/symlink this repository's "A5sys"-folder inside the phpcs `Standards` directory

4. Check the installed coding standards for "A5sys"

        phpcs -i

5. Done!

        phpcs /path/to/code
