#! /bin/sh

HELP="$0 Object-Name"
if [ -z "$*" ] ; then
  echo $HELP
  exit
fi

for DB in sqlite sqlite3 mysql mysqli postgresql ; do
  echo "================================${DB}==========================="
  php test_generic.php --db-engine $DB $@
  echo "================================${DB}==========================="
done | tee run_test.out | less
