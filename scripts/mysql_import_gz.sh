gzip -dc dump.sql.gz | docker exec -i $(docker-compose ps -q mysql) bash -c 'exec mysql -uroot --password=$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE'
