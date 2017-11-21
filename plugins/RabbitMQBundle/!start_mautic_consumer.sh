#!/bin/bash
#This file for: quickly start the RabbitMQ Mautic consumer
#---------------------------------------

screen -dmS mautic_consumer php ../app/console rabbitmq:consumer:mautic && screen -ls