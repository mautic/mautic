#!/bin/bash
#This file for: quickly start the RabbitMQ Mautic consumer
#---------------------------------------

if [ $(screen -ls|grep 'mautic_consumer'|wc -l) -lt 1 ] 
then
	screen -L -dmS mautic_consumer php ../../app/console rabbitmq:consumer:mautic && screen -ls && exit 0
fi
exit 0
