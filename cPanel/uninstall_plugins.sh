#!/bin/sh
##############################################################################
#                         _        _
#                        | |      | |
#    _ __ ___   __ _ _ __| | _____| |_ __ _  ___   ___
#   | '_ ` _ \ / _` | '__| |/ / _ \ __/ _` |/ _ \ / _ \
#   | | | | | | (_| | |  |   <  __/ || (_| | (_) | (_) |
#   |_| |_| |_|\__,_|_|  |_|\_\___|\__\__, |\___/ \___/
#                                      __/ |
#   MarketGoo Plug-in for cPanel      |___/
#
##############################################################################

WHITE=$(tput setaf 7 ; tput bold)
RESET=$(tput sgr0)
CWD=`dirname $0`
UNINSTALL_CMD=/usr/local/cpanel/bin/unregister_cpanelplugin
THEMEDIR=/usr/local/cpanel/base/frontend/
THEMES=`find $THEMEDIR -maxdepth 1 -type d -exec basename {} \; | tail -n +2`
MKTGOODIR=/var/cpanel/marketgoo

display_progress()
{
    echo "${WHITE}0%           25%            50%           75%           100%${RESET}"
}

advance_progress()
{
    echo -n "==============="
}

uninstall_libraries()
{
        rm -rf /usr/local/cpanel/share/MarketGoo
        rm -f /usr/local/cpanel/etc/MarketGoo.ini
}

uninstall_cpanel_plugin()
{
    for i in $THEMES; do
        rm -rf $THEMEDIR/$i/marketgoo/ >/dev/null 2>&1
        rm -rf $THEMEDIR/$i/dynamicui/dynamicui_marketgoo* >/dev/null 2>&1
    done
    rm -rf $MKTGOODIR
}

echo "${WHITE}Uninstalling cPanel Plugins${RESET} (This may take a couple minutes)"

# Uninstall plugins
display_progress && $UNINSTALL_CMD $CWD/plugins/website_marketing_tools.cpanelplugin >/dev/null 2>&1

# Uninstall the Group
advance_progress && $UNINSTALL_CMD $CWD/plugins/marketgoo.cpanelplugin >/dev/null 2>&1

advance_progress && uninstall_libraries

advance_progress && uninstall_cpanel_plugin

advance_progress && /usr/local/cpanel/bin/rebuild_sprites >/dev/null 2>&1

echo
echo "${GREEN}*** DONE ***${RESET}"
echo
