docker exec -i $(docker-compose ps -q mysql) bash -c 'export MYSQL_PWD=$MYSQL_ROOT_PASSWORD; mysqldump --force --opt --comments=false --quote-names --single_transaction --routines --events -uroot $MYSQL_DATABASE' | gzip -c > dump.sql.gz
