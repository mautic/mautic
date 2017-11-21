#!/bin/bash
#This file for: quickly stop the RabbitMQ Mautic consumer
#---------------------------------------

screen -X -S mautic_consumer quit && screen -ls