#!/bin/sh
##############################################################################
#                         _        _
#                        | |      | |
#    _ __ ___   __ _ _ __| | _____| |_ __ _  ___   ___
#   | '_ ` _ \ / _` | '__| |/ / _ \ __/ _` |/ _ \ / _ \
#   | | | | | | (_| | |  |   <  __/ || (_| | (_) | (_) |
#   |_| |_| |_|\__,_|_|  |_|\_\___|\__\__, |\___/ \___/
#                                      __/ |
#   marketgoo Plug-in for cPanel      |___/
#
##############################################################################

WHITE=$(tput setaf 7 ; tput bold)
RESET=$(tput sgr0)
CWD=`dirname $0`
INSTALL_CMD=/usr/local/cpanel/bin/register_cpanelplugin
INSTALL_CMD_44=/usr/local/cpanel/scripts/install_plugin
MKTGOODIR=/var/cpanel/marketgoo
SRCDIR=${CWD}
THEMEDIR=/usr/local/cpanel/base/frontend/
THEMES=`find $THEMEDIR -maxdepth 1 -type d -exec basename {} \; | tail -n +2`
TEMPDIR=/
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

url=$1

display_progress()
{
    echo "${WHITE}0%           25%            50%           75%           100%${RESET}"
}

install_libraries()
{
    declare -a copyFiles=(
        'marketgooIncludes' '/usr/local/cpanel/share/marketgoo'
    )
    configFile='/usr/local/cpanel/etc/marketgoo.ini';

    if [ -z "$url" ]
    then
        echo -n "
Enter WHMCS endpoint URL:
:";
        read url;
    fi

    echo "
    endpoint='$url'
    " > "$configFile"

    for dir in "${copyFiles[@]}"
    do
    if([[ -z "$source" ]])
    then
        source=$dir;
    else
        if [ ! -d "$dir" ]
        then
            mkdir "$dir"
        fi;
        cp -fR "$CWD/$source/." "$dir"
        source='';
    fi;
    done
}

advance_progress()
{
    echo -n "==============="
}

install_plugin()
{
    mkdir -p $MKTGOODIR
    cp -r $SRCDIR/plugins $MKTGOODIR >/dev/null 2>&1
    cp -f $SRCDIR/install_plugins.sh $MKTGOODIR >/dev/null 2>&1
    cp -f $SRCDIR/uninstall_plugins.sh $MKTGOODIR >/dev/null 2>&1
    for i in $THEMES; do cp -r $SRCDIR/marketgoo $THEMEDIR/$i/ ; done
}

echo "${WHITE}Installing cPanel Plugins${RESET}   (This may take a couple minutes)"
    
display_progress

sh plugins/compress.sh

advance_progress && install_plugin

if [ -x ${INSTALL_CMD_44} ]; then

    # Create the Group an Plugin using the new 11.44+ cPanel version
    advance_progress && ${INSTALL_CMD_44} $CWD/plugins/x3.tar.gz --theme x3  >/dev/null 2>&1
    advance_progress && ${INSTALL_CMD_44} $CWD/plugins/paperlantern.tar.gz --theme paper_lantern >/dev/null 2>&1
    advance_progress && install_libraries
else
    # Create the Group
    $INSTALL_CMD $CWD/plugins/marketgoo.cpanelplugin >/dev/null 2>&1

    # Create plugins
    advance_progress && $INSTALL_CMD $CWD/plugins/website_marketing_tools.cpanelplugin >/dev/null 2>&1
    advance_progress && install_libraries
fi

advance_progress && /usr/local/cpanel/bin/rebuild_sprites >/dev/null 2>&1

echo
echo "${GREEN}*** DONE ***${RESET}"
echo
