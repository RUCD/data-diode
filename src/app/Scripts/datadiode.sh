#!/bin/bash
interface=$2
input_port=$3
destination=$4
output_port=$5
mac=$2
ip=$3
case $1 in
    "add")
        iptables -t nat -A PREROUTING -i $interface -p udp --dport $input_port -j DNAT --to $destination:$output_port
        exit $?
        ;;
    "remove")
        iptables -t nat -D PREROUTING -i $interface -p udp --dport $input_port -j DNAT --to $destination:$output_port
        exit $?
        ;;
    "save")
        iptables-save > /etc/iptables/rules.v4 \
        && ip6tables-save > /etc/iptables/rules.v6
        exit $?
        ;;
    "disablenat")
        iptables -t nat -D POSTROUTING -o $interface -j MASQUERADE
        ;;
    "nat")
        iptables -t nat -A POSTROUTING -o $interface -j MASQUERADE
        exit $?
        ;;
    "fluship")
        ip addr flush $interface
        exit $?
        ;;
    "restartnetwork")
        shift
        /etc/init.d/networking restart
        exit $?
        ;;
    "arp")
        ip neighbor flush all \
        && echo "$mac $ip" > /etc/ethers \
        && arp -f /etc/ethers
        exit $?
        ;;
    *)
        echo "Invalid option"
        exit 1
        ;;
esac
exit 0
