## Synopsis

This project is a Plugin to Send Mobile Text Messages using InfoBip API.
Mautic have integration with Twillio only, so, this plugin is a new alternative to the users of Mautic.

## Code Example

The development of this plugin is simples and easy.
I'm using all the structure of the base SMSBundle, in the config file, I point almost all classes to the original Bundle, but, in the API I created a InfobipAPI and pointed in config files, passing the necessary parameters to make work. 

## Motivation

Mautic offers only Twillio API integration and in our project, we use InfoBip to send SMS to our leads, so, I had to work in a solution for this.

## Installation

Put the InfoBipSmsBundle in the plugins folder.
Go to the Configuration -> TextMessages Settings.
Enable Text Message.
Put your user and password of the InfoBip Service and Save.

## Tests

After configure the plugin.
1 - Go to Channels -> Text Messages.
2 - Create a text message with any content.
3 - Go to Components -> Form.
4 - Create a new Form (New Campaign Form).
5 - Create the form with a email or mobile number field.
6 - Go to Campaigns.
7 - Create a new campaign: Contact Sources: Campaign Form.
8 - Choose the form you created earlier.
9 - In the next step, select Action.
10 - In the select box, chose InfoBip Send text messages.
11 - In the box of InfoBip Send text messages, put a name and choose the form message that you created earlier.
12 - Save your campaign.
13 - Go to Contacts.
14 - Create a contact with a valid mobile number.
15 - Go to Components -> Form.
16 - Click in Preview in the form that you created.
17 - Send this form with the contact information.
18 - Execute the command php console mautic:campaigns:trigger. 
19 - The contact used to fill the form, will receive the text message.

## Contributors

@abreuleonel
