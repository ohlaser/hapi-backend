#!/bin/bash

modepath=../data/testmode

# check status
status() {
    if [ -s $modepath ]; then
        echo "test mode."
    else
        echo "not test mode."
    fi
}

# enable test mode
enable() {
    echo "1" > $modepath
}

# disable test mode
disable() {
    > $modepath
}


case "$1" in
    status)
        status
        ;;
    enable)
        enable
        ;;
    disable)
        disable
        ;;
    *)
        echo "Usage: $0 {status|enable|disable}"
        exit 1
        ;;
esac
