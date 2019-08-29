#docker-compose pull
docker-compose kill
docker-compose rm -vf
docker-compose up -d
docker-compose ps
docker-compose logs --tail=100
#docker-compose logs -f --tail=100
