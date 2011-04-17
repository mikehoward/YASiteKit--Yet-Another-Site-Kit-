#! /bin/sh

# guess httpd userid and group
if [ -s fix_perms.out ] ; then
	. fix_perms.out
else
	HTTPD_UID="`ps -el | grep httpd | awk '$1 != 0 { print $1 ; exit ;}'`"
	SCRIPT_USER="\$3 == $HTTPD_UID { print \$1 ; exit ;}"
	HTTPD_USER="`awk -F: "$SCRIPT_USER" /etc/passwd`"
	SCRIPT_GID="\$3 == $HTTPD_UID { print \$4 ; exit ;}"
	HTTPD_GID="`awk -F: "$SCRIPT_GID" /etc/passwd`"
	SCRIPT_GROUP="\$3 == $HTTPD_GID { print \$1 ; exit ; }"
	HTTPD_GROUP="`awk -F: "$SCRIPT_GROUP" /etc/group`"
fi

fix_dir() {
	DIR=$1
	echo "Fixing permissions for $DIR"
	test -d $DIR || mkdir $DIR
	sudo chgrp $HTTPD_GROUP $DIR
	sudo chmod g+ws $DIR
}

fix_files() {
	FILES=$@
	echo "Fixing permissions for $FILES"
	sudo chgrp $HTTPD_GROUP $FILES
	sudo chmod g+ws $FILES
}

save() {
	echo "HTTPD_GROUP='$HTTPD_GROUP'" >fix_perms.out
	echo "HTTPD_USER='$HTTPD_USER'" >>fix_perms.out
}

do_fix_files() {
	fix_dir config-dir
	fix_dir ../dump.d
	fix_dir ../sqlite_db.d
  fix_dir ../products
  fix_dir ../images
  fix_dir ../images/email_pic
  fix_dir ../images/rma_photos
	fix_files ../sqlite_db.d/*
	fix_dir ../../tarfiles
	fix_files ../../tarfiles/*
}

PROGNAME="`basename $0`"
USAGE="$PROGNAME"

if [ $# -gt 0 ] ; then
	case $1 in
		-b|--batch)
			do_fix_files
			save
			;;
		*) echo `basename $0` [-b | --batch] ;;
	esac
else
	echo "$PROGNAME is an interactive program which changes the group and group permissions
	of config-dir, ../dump.d, and ../sqlite_db.d so that the web server can write to these directories.
	This is necessary in order for the Configurator and Installerator to work, for
	the Site to be able to create data archives which can be used to rebuild the database,
	and to use SQLITE to create a database
	"

	while true ; do
		echo "httpd user name: $HTTPD_USER"
		echo "httpd group name: $HTTPD_GROUP"
		echo "(U <username> | G <group name) | Fix | Quit) ? "
		read rsp rest
		case $rsp in
			[uU]*) shift ; HTTPD_USER=$1 ; shift ;;
			[gG]*) shift ; HTTPD_GROUP=$1 ; shift ;;
			[fF]*)
				do_fix_files
				save
				;;
			[qQ]*) echo "Quiting" ; save ; exit ;;
			*) echo "Huh? don't understand $rsp" ;;
		esac
	done
fi