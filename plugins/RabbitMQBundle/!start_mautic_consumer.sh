#!/bin/bash
#This file for: quickly start the RabbitMQ Mautic consumer
#---------------------------------------

screen -L -dmS mautic_consumer php ../../app/console rabbitmq:consumer:mautic && screen -ls && exit 0