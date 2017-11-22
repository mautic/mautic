#!/bin/bash
#This file for: quickly start the RabbitMQ Salesforce consumer
#---------------------------------------

screen -dmS salesforce_consumer php ../app/console rabbitmq:consumer:salesforce && screen -ls