### Testing

##### within docker container

docker run --rm -ti -v $PWD:/app olekhy/webapp bash -c "composer i && composer check"
