#!/bin/bash

setup_mautic() {
    printf "Installing Mautic Composer dependencies...\n"
    composer install

    cp ./.ddev/local.config.php.dist ./app/config/local.php

    printf "Installing Mautic...\n"
    php bin/console mautic:install https://${DDEV_HOSTNAME} \
        --mailer_from_name="DDEV" --mailer_from_email="mautic@ddev.local" \
        --mailer_transport="smtp" --mailer_host="localhost" --mailer_port="1025"
    php bin/console cache:warmup --no-interaction --env=dev

    printf "Enabling plugins...\n"
    php bin/console mautic:plugins:reload

    tput setaf 2
    printf "All done! Here's some useful information:\n"
    printf "🔒 The default login is admin/mautic\n"
    printf "🌐 To open the Mautic instance, go to https://${DDEV_HOSTNAME} in your browser.\n"
    printf "🌐 To open PHPMyAdmin for managing the database, go to https://${DDEV_HOSTNAME}:8037 in your browser.\n"
    printf "🌐 To open MailHog for seeing all emails that Mautic sent, go to https://${DDEV_HOSTNAME}:8026 in your browser.\n"
    printf "🚀 Run \"ddev exec composer test\" to run PHPUnit tests.\n"
    printf "🚀 Run \"ddev exec bin/console COMMAND\" (like mautic:segments:update) to use the Mautic CLI. For an overview of all available CLI commands, go to https://mau.tc/cli\n"
    printf "🔴 If you want to stop the instance, simply run \"ddev stop\".\n"
    tput sgr0
}

# Check if the user has indicated their preference for the Mautic installation
# already (DDEV-managed or self-managed)
if ! test -f ./.ddev/mautic-preference
then
    tput setab 3
    tput setaf 0
    printf "Do you want us to set up the Mautic instance for you with the recommended settings for DDEV?\n"
    printf "If you answer \"no\", you will have to set up the Mautic instance yourself."
    tput sgr0
    printf "\nAnswer [yes/no]: "
    read MAUTIC_PREF

    if [[ $MAUTIC_PREF == "yes" ]]
    then
        printf "Okay, setting up your Mautic instance... 🚀\n"
        echo "ddev-managed" > ./.ddev/mautic-preference
        setup_mautic
    else
        printf "Okay, you'll have to set up the Mautic instance yourself. That's what pros do, right? Good luck! 🚀\n"
        echo "unmanaged" > ./.ddev/mautic-preference
    fi
fi
