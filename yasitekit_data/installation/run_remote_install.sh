#! /bin/sh

PROGNAME="`basename $0`"
HELP="${PROGNAME} [-h|--help|help] <alpha|production>

First verifies that the configuration file for the selected site installation
exists and that the upload script (and friends) have been updated
-after- the last save of the configuration file.

If all is well, then runs the upload script
"

while [ $# -gt 0 ] ; do
  case $1 in
    alpha|production) SITE_INSTALLATION=$1 ; shift ;;
    -h|--help|help) echo "$HELP" ; exit 0 ;;
    *) echo "Illegal Option: $1" ; echo "$HELP" ; exit 1 ;;
  esac
done

if [ -z "${SITE_INSTALLATION}" ] ; then
	echo "$HELP"
	exit 1
fi

if [ ! -s config-dir/config.php-${SITE_INSTALLATION} ] ; then
	echo "You need to run the Configurator and Create a ${SITE_INSTALLATION} Configuration"
	exit
elif [ ! -s config-dir/upload_script.sh-${SITE_INSTALLATION} ] ; then
	echo "You need to run the Installerator and Create a Local ${SITE_INSTALLATION} Installation Script"
	exit
elif [ config-dir/config.php-${SITE_INSTALLATION} -nt config-dir/upload_script.sh-${SITE_INSTALLATION} ] ; then
	echo "You need to run the Installerator and Update the Local ${SITE_INSTALLATION} Installation Script"
	exit
fi

(
	cd config-dir
	/bin/sh ./make_tarfiles.sh-development
 	/bin/sh ./upload_script.sh-${SITE_INSTALLATION}
)
