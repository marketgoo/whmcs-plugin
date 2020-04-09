#!/bin/sh
# marketgoo: install plugin

target=$1
if [ -z "$target" ]
then
      echo "Please add target directory as parameter"
      exit 1
fi

hook=includes/hooks/marketgoo.php
rm -f $target/$hook
cp $hook $target/$hook
# hookorigin=$(readlink -f $hook)
# ln -s $hookorigin $target/$hook

server=modules/servers/marketgoo
rm -Rf $target/$server
cp -R $server $target/$server
# serverorigin=$(readlink -f $server)
# ln -s $serverorigin $target/$server

