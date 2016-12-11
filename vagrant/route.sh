#!/bin/sh

DEFAULTROUTE=$(ip route show | grep 10.0.2.2 | wc -l)

if [ $DEFAULTROUTE -eq 0 ]; then
    sudo ip route add default via 10.0.2.2 dev eth0
fi

if [ $DEFAULTROUTE -gt 0 ]; then
    sudo ip route chg default via 10.0.2.2 dev eth0
fi