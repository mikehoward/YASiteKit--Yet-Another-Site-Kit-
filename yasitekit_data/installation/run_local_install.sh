#! /bin/sh

echo "Running fix_perms.sh to fix local permissions"
sh fix_perms.sh -b

if [ ! -s config-dir/config.php-development ] ; then
	echo "You need to run the Configurator and Create a Development Configuration"
	exit
elif [ ! -s config-dir/local_install.sh-development ] ; then
	echo "You need to run the Installerator and Create a Local Development Installation Script"
	exit
elif [ config-dir/config.php-development -nt config-dir/local_install.sh-development ] ; then
	echo "You need to run the Installerator and Update the Local Development Installation Script"
	exit
fi

/bin/sh config-dir/local_install.sh-development

php bootstrap_db.php

echo "Creating all missing AClass Tables\n";
php dbaccess_create_all_tables.php

# run fix_perms.sh in batch mode to make sure all permissions are still OK
sh fix_perms.sh -b
