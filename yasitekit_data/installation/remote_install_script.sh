#! /bin/sh

PROGNAME="`basename $0`"
USAGE="${PROGNAME} site-name <development|alpha|production>"

if [ $# -ne 2 ] ; then
  echo $USAGE
  exit 1
fi
SITE=$1
case $2 in
  dev*) INSTALLATION=development ;;
  alp*) INSTALLATION=alpha ;;
  pro*) INSTALLATION=production ;;
  *) echo "Installtion Type Error: $2" ; echo $USAGE ; exit 1 ;;
esac
SITE_DIR=${SITE}.${INSTALLATION}

test -d ~/private_data || mkdir ~/private_data
test -d ~/private_data/${SITE_DIR} || mkdir ~/private_data/${SITE_DIR}
test -d ~/private_data/${SITE_DIR}/dev-dump.d || mkdir ~/private_data/${SITE_DIR}/dev-dump.d

( cd public_html/${SITE_DIR} ;
  tar xzf ~/${SITE}-docroot.tar.gz
  mv htaccess-${INSTALLATION} .htaccess
)
( cd private_data/${SITE_DIR} ; 
  test -d system || mkdir system
  tar xzf ~/${SITE}-private.tar.gz
  if [ -s version ] ; then
    VERSION=`cat version`
    tar xzf ~/private-system-${VERSION}.tar.gz
  else
    echo "ERROR: Version file 'version' not found - System Not Unpacked"
  fi
)
(
  cd private_data/${SITE_DIR}/dev-dump.d
  tar xzf ~/${SITE}-dump.tar.gz
)
echo "________________________________________________________________________________"
echo ""
echo "Please Log in As Administrator and Go to the 'Dump and Reload Database' Function"
echo ""
echo "________________________________________________________________________________"
