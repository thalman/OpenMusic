#!/bin/sh

DIR=$HOME/openmusic.halman.net


# 33 is the www-data user in the container
# podman unshare chown 33:33 -R $DIR/*

podman run --name openmusic.halman.net --rm -d -p 8080:80 \
    -v $DIR/drupal/modules:/var/www/html/modules:Z \
    -v $DIR/drupal/profiles:/var/www/html/profiles:Z \
    -v $DIR/drupal/sites:/var/www/html/sites:Z \
    -v $DIR/drupal/themes:/var/www/html/themes:Z \
    -v $HOME/OM.git:/var/www/html/OM.git:Z \
    openmusic
