# Outlook Plugin for Mautic
This plugin allows for the [Outlook Add-In](https://github.com/virlatinus/MauticOutlookPlugin/) to keep track of emails sent to leads.

The Add-In source code can be found at [https://github.com/virlatinus/MauticOutlookPlugin/](https://github.com/virlatinus/MauticOutlookPlugin/)

## URL Parameter Length Issue
; Please note that PHP setups with the suhosin patch installed will have a                                   
; default limit of 512 characters for get parameters. Although bad practice,                                 
; most browsers (including IE) supports URLs up to around 2000 characters,                                   
; while Apache has a default of 8000.                                                                        

; To add support for long parameters with suhosin add the following to php.ini                                                         
suhosin.get.max_value_length = 5000
