#!/bin/bash
#This file for: quickly stop the RabbitMQ Salesforce consumer
#---------------------------------------

screen -X -S salesforce_consumer quit && screen -ls