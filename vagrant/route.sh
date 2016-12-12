#!/bin/sh

DEFAULTROUTE=$(ip route show | grep 10.0.2.2 | wc -l)

isSemantic=$(ifconfig -a | grep eth0)

if [ "$isSemantic" != "" ]; then
    interf="eth0"
else
    interf="enp0s3"
fi

if [ $DEFAULTROUTE -eq 0 ]; then
    sudo ip route add default via 10.0.2.2 dev $interf
fi

if [ $DEFAULTROUTE -gt 0 ]; then
    sudo ip route chg default via 10.0.2.2 dev $interf
fi