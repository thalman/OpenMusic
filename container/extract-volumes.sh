#!/bin/sh

DIR=`pwd`/drupal
if [ ! -d "$DIR" ] ; then
    mkdir "$DIR"
fi

podman run --rm openmusic tar -cC /var/www/html sites | tar -xC $DIR
