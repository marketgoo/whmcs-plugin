#!/bin/sh
# marketgoo: uninstall plugin

target=$1
if [ -z "$target" ]
then
      echo "Please add target directory as parameter"
      exit 1
fi

hook=includes/hooks/marketgoo.php
rm -f $target/$hook

server=modules/servers/marketgoo
rm -Rf $target/$server

