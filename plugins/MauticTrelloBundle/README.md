# Mautic Trello Integration
Interact with Trello directly from Mauitc. E.g. create Trello cards for contacts.

The api is based on OpenAPI v3.

## Requirements
- Mautic v3.0.1
- Trello

## Install OpenAPI generator
```
npm install
```

## Run tests
```
bin/phpunit --bootstrap vendor/autoload.php --configuration app/phpunit.xml.dist --filter MauticTrelloBundle
```

## API Documentation
- [Overview](Openapi/README.md)

## Enduser documentation
https://github.com/mautic/mautic-documentation/tree/master/pages/12.Plugins/17.Trello


# Experimental

## Mock Server for UnitTests

Can be combined with [prism](https://github.com/stoplightio/prism) to automatically create a mock server based on the OpenAPI specification. This is not in use for now. Static json files are used for the UnitTests.

### Start mock server

```
prism mock -d docs/api/i2-trello.oas3.yml
```