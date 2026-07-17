# PHPStan ignore annotations removed

147 `@phpstan-ignore*` annotations removed from `app/` and `plugins/`, across 70 files. Each one suppressed a PHPStan error that is now reported again.

## By error identifier

| Identifier | Count |
| --- | --- |
| `(no identifier)` | 62 |
| `classConstant.deprecatedClass` | 31 |
| `new.deprecated` | 14 |
| `classConstant.deprecated` | 7 |
| `property.notFound` | 5 |
| `class.extendsFinalByPhpDoc` | 4 |
| `method.deprecated` | 4 |
| `method.notFound` | 3 |
| `parameter.deprecatedClass` | 3 |
| `parameterByRef.unusedType` | 2 |
| `return.deprecatedClass` | 2 |
| `trait.unused` | 2 |
| `argument.unresolvableType` | 1 |
| `deadCode.unreachable` | 1 |
| `function.impossibleType` | 1 |
| `method.childReturnType` | 1 |
| `new.resultUnused` | 1 |
| `parameterByRef.type` | 1 |
| `phpunit.callParent` | 1 |
| `symfonyContainer.privateService` | 1 |

## By file

### app/bundles/CampaignBundle/Config/config.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 328 | `ignore-next-line` | — | — |

### app/bundles/CampaignBundle/Controller/EventController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 54 | `ignore-next-line` | — | Ignore as AbstractStandardFormController is deprecated |

### app/bundles/CampaignBundle/Tests/Entity/CampaignRepositoryFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 142 | `ignore` | `parameterByRef.unusedType` | — |

### app/bundles/CampaignBundle/Tests/Executioner/Dispatcher/ActionDispatcherTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 39 | `ignore` | `classConstant.deprecatedClass` | — |

### app/bundles/CampaignBundle/Tests/Executioner/Dispatcher/DecisionDispatcherTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 33 | `ignore` | `classConstant.deprecatedClass` | — |

### app/bundles/CampaignBundle/Tests/Executioner/Dispatcher/LegacyEventDispatcherTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 104 | `ignore` | `classConstant.deprecatedClass` | — |
| 108 | `ignore` | `classConstant.deprecatedClass` | — |
| 109 | `ignore-line` | `classConstant.deprecated` | — |
| 153 | `ignore` | `classConstant.deprecatedClass` | — |
| 154 | `ignore-line` | `classConstant.deprecated` | — |
| 200 | `ignore` | `classConstant.deprecatedClass` | — |
| 205 | `ignore` | `classConstant.deprecatedClass` | — |
| 206 | `ignore-line` | `classConstant.deprecated` | — |
| 255 | `ignore` | `classConstant.deprecatedClass` | — |
| 260 | `ignore` | `classConstant.deprecatedClass` | — |
| 261 | `ignore-line` | `classConstant.deprecated` | — |
| 305 | `ignore` | `classConstant.deprecatedClass` | — |
| 310 | `ignore` | `classConstant.deprecatedClass` | — |
| 311 | `ignore-line` | `classConstant.deprecated` | — |
| 356 | `ignore` | `classConstant.deprecatedClass` | — |
| 395 | `ignore` | `classConstant.deprecatedClass` | — |
| 430 | `ignore` | `classConstant.deprecatedClass` | — |
| 432 | `ignore` | `return.deprecatedClass` | — |
| 434 | `ignore` | `classConstant.deprecatedClass` | — |
| 445 | `ignore` | `return.deprecatedClass` | — |
| 449 | `ignore` | `new.deprecated` | — |

### app/bundles/CampaignBundle/Tests/Executioner/EventExecutionerLockTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 31 | `ignore` | `phpunit.callParent` | — |
| 127 | `ignore` | `parameter.deprecatedClass` | — |
| 131 | `ignore` | `classConstant.deprecated` | — |
| 138 | `ignore` | `classConstant.deprecated` | — |

### app/bundles/CampaignBundle/Tests/Functional/EventListener/CampaignSubscriberFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 70 | `ignore` | `new.deprecated` | — |

### app/bundles/CategoryBundle/Model/CategoryModel.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 50 | `ignore-next-line` | `method.childReturnType` | — |

### app/bundles/ChannelBundle/Tests/EventListener/CampaignSubscriberTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 90 | `ignore` | `new.deprecated` | — |
| 226 | `ignore` | `parameter.deprecatedClass` | — |

### app/bundles/ConfigBundle/Controller/ConfigController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 270 | `ignore` | `function.impossibleType` | Not sure what this is about) |

### app/bundles/ConfigBundle/Controller/SysinfoController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 35 | `ignore-next-line` | — | Ignore as AbstractStandardFormController is deprecated |

### app/bundles/CoreBundle/Controller/CommonController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 177 | `ignore` | `parameterByRef.type` | — |

### app/bundles/CoreBundle/Entity/CommonRepository.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 461 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 467 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 484 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 1478 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 1485 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 1504 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 1513 | `ignore-next-line` | — | $q accepts ORM and DBAL QueryBuilder; add() is deprecated only on DBAL CompositeExpression, not on ORM Andx |
| 1751 | `ignore-line` | — | we are iterating over StdClass. We should refactor this into a collection of DTO objects in M6 |

### app/bundles/CoreBundle/Entity/UpsertTrait.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 7 | `ignore` | `trait.unused` | prepared for future use) |

### app/bundles/CoreBundle/Model/VariantModelTrait.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 88 | `ignore-line` | — | @todo for M6, extend the TranslationEntityInterface from The VariantEntityInterface |

### app/bundles/CoreBundle/Security/Permissions/CorePermissions.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 89 | `ignore` | `method.deprecated` | — |

### app/bundles/CoreBundle/Service/OptimisticLockService.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 48 | `ignore` | `parameterByRef.unusedType` | — |

### app/bundles/CoreBundle/Test/Container/TestContainer.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 20 | `ignore-line` | — | — |
| 21 | `ignore-line` | — | — |

### app/bundles/CoreBundle/Tests/Functional/Service/LocalFileAdapterServiceTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 35 | `ignore` | `symfonyContainer.privateService` | — |

### app/bundles/CoreBundle/Tests/Unit/Doctrine/Mapping/GeneratedColumn/GeneratedColumnsTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 49 | `ignore` | `method.deprecated` | — |
| 60 | `ignore` | `method.deprecated` | — |

### app/bundles/CoreBundle/Tests/Unit/Form/Type/SortableValueLabelListTypeTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 107 | `ignore-next-line` | — | — |
| 110 | `ignore-next-line` | — | — |

### app/bundles/CoreBundle/Translation/TranslatorLoader.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 10 | `ignore-next-line` | `class.extendsFinalByPhpDoc` | — |

### app/bundles/EmailBundle/Tests/Controller/EmailFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 42 | `ignore-line` | — | — |

### app/bundles/EmailBundle/Tests/Entity/EmailTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 67 | `ignore` | `property.notFound` | — |
| 77 | `ignore` | `property.notFound` | — |
| 85 | `ignore` | `property.notFound` | — |
| 86 | `ignore` | `property.notFound` | — |
| 93 | `ignore` | `property.notFound` | — |

### app/bundles/EmailBundle/Tests/EventListener/CampaignConditionSubscriberTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 59 | `ignore-next-line` | — | CampaignExecutionEvent is deprecated but needed for this test) |
| 92 | `ignore-next-line` | — | CampaignExecutionEvent is deprecated but needed for this test) |
| 127 | `ignore-next-line` | — | CampaignExecutionEvent is deprecated but needed for this test) |

### app/bundles/EmailBundle/Tests/Helper/MailHelperTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 695 | `ignore-line` | — | as it's testing a deprecated method |
| 711 | `ignore-line` | — | as it's testing a deprecated method |
| 739 | `ignore-line` | — | as it's testing a deprecated method |
| 746 | `ignore-line` | — | as it's testing a deprecated method |
| 753 | `ignore-line` | — | as it's testing a deprecated method |
| 760 | `ignore-line` | — | as it's testing a deprecated method |
| 767 | `ignore-line` | — | as it's testing a deprecated method |
| 774 | `ignore-line` | — | as it's testing a deprecated method |
| 781 | `ignore-line` | — | as it's testing a deprecated method |
| 788 | `ignore-line` | — | as it's testing a deprecated method |

### app/bundles/EmailBundle/Tests/Helper/Transport/BatchTransport.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 21 | `ignore-line` | — | — |

### app/bundles/EmailBundle/Tests/Helper/Transport/BcInterfaceTokenTransport.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 17 | `ignore-line` | — | — |

### app/bundles/EmailBundle/Tests/Helper/Transport/SmtpTransport.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 16 | `ignore-line` | — | — |

### app/bundles/FormBundle/Controller/FieldController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 59 | `ignore-next-line` | — | FormController extends deprecated AbstractStandardFormController; fix requires class hierarchy refactoring |

### app/bundles/FormBundle/Controller/FormController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 59 | `ignore-next-line` | — | FormController extends deprecated AbstractStandardFormController; fix requires class hierarchy refactoring |

### app/bundles/FormBundle/Controller/ResultController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 61 | `ignore-next-line` | — | FormController extends deprecated AbstractStandardFormController; fix requires class hierarchy refactoring |

### app/bundles/FormBundle/Entity/Field.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 1017 | `ignore-next-line` | — | — |

### app/bundles/FormBundle/EventListener/FormImportExportSubscriber.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 70 | `ignore-line` | — | — |

### app/bundles/FormBundle/Tests/Controller/FormControllerFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 206 | `ignore-next-line` | — | using the deprecated method on purpose) |

### app/bundles/FormBundle/Tests/EventListener/CampaignSubscriberFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 106 | `ignore` | `new.deprecated` | — |

### app/bundles/IntegrationsBundle/Tests/Functional/Services/SyncService/SyncServiceTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 32 | `ignore-next-line` | `deadCode.unreachable` | — |

### app/bundles/IntegrationsBundle/Tests/Unit/Auth/Provider/ApiKey/HttpFactoryTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 105 | `ignore-line` | — | Deprecated. Must be refactored for Guzzle 8 |

### app/bundles/IntegrationsBundle/Tests/Unit/Auth/Provider/Oauth1aTwoLegged/HttpFactoryTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 37 | `ignore-line` | — | Deprecated. Must be refactored for Guzzle 8 |

### app/bundles/IntegrationsBundle/Tests/Unit/Auth/Provider/Oauth2ThreeLegged/HttpFactoryTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 137 | `ignore-next-line` | — | — |
| 323 | `ignore-next-line` | — | — |

### app/bundles/IntegrationsBundle/Tests/Unit/Auth/Provider/Oauth2TwoLegged/HttpFactoryTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 364 | `ignore-line` | — | Deprecated. Must be refactored for Guzzle 8 |

### app/bundles/LeadBundle/Controller/ImportController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 76 | `ignore-next-line` | — | FormController extends deprecated AbstractStandardFormController; fix requires class hierarchy refactoring |

### app/bundles/LeadBundle/Entity/LeadList.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 323 | `ignore` | `method.deprecated` | — |

### app/bundles/LeadBundle/Entity/OperatorListTrait.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 204 | `ignore-line` | — | based on https://github.com/phpstan/phpstan/issues/9095 (Call to function property_exists() with ...  'translator' will always evaluate to false.) |

### app/bundles/LeadBundle/EventListener/CampaignSubscriber.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 799 | `ignore` | `parameter.deprecatedClass` | — |

### app/bundles/LeadBundle/Form/Type/FilterTrait.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 22 | `ignore-next-line` | `trait.unused` | — |

### app/bundles/LeadBundle/Segment/Query/QueryBuilder.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 76 | `ignore-line` | — | this method is deprecated. We'll have to find a way how to refactor this method. |

### app/bundles/LeadBundle/Tests/Entity/ImportTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 94 | `ignore` | `argument.unresolvableType` | I don't see anything wrong) |

### app/bundles/LeadBundle/Tests/Entity/LeadRepositoryTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 194 | `ignore-line` | — | this tests if we provide null instead which actually happens. |

### app/bundles/LeadBundle/Tests/Entity/LeadTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 130 | `ignore` | `method.notFound` | — |
| 133 | `ignore` | `method.notFound` | — |
| 134 | `ignore` | `method.notFound` | — |

### app/bundles/LeadBundle/Tests/EventListener/CampaignSubscriberFunctionalTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 262 | `ignore` | `new.deprecated` | — |
| 1059 | `ignore` | `new.deprecated` | — |
| 1120 | `ignore-next-line` | `new.deprecated` | — |

### app/bundles/LeadBundle/Tests/EventListener/CampaignSubscriberTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 233 | `ignore` | `new.deprecated` | — |
| 271 | `ignore` | `new.deprecated` | — |
| 315 | `ignore` | `new.deprecated` | — |
| 360 | `ignore` | `new.deprecated` | — |
| 405 | `ignore` | `new.deprecated` | — |

### app/bundles/LeadBundle/Tests/Functional/EventListener/CampaignSubscriberTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 85 | `ignore` | `new.deprecated` | — |
| 148 | `ignore` | `new.deprecated` | — |
| 151 | `ignore` | `classConstant.deprecatedClass` | — |
| 187 | `ignore-line` | `classConstant.deprecatedClass` | — |
| 190 | `ignore-line` | `classConstant.deprecatedClass` | — |

### app/bundles/LeadBundle/Tests/Model/FieldModelTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 297 | `ignore-line` | — | SQLLogger is deprecated |

### app/bundles/NotificationBundle/Tests/Form/Type/MobileNotificationDetailsTypeTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 35 | `ignore-next-line` | — | — |

### app/bundles/PluginBundle/Controller/PluginController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 436 | `ignore-next-line` | — | Ignore as AbstractIntegration is deprecated |

### app/bundles/PluginBundle/Tests/DependencyInjection/Compiler/TestPass.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 23 | `ignore-next-line` | — | Ignore as AbstractIntegration is deprecated |

### app/bundles/PluginBundle/Tests/Form/Type/DetailsTypeTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 25 | `ignore` | `classConstant.deprecatedClass` | — |
| 94 | `ignore` | `classConstant.deprecatedClass` | — |
| 176 | `ignore` | `classConstant.deprecatedClass` | — |
| 259 | `ignore` | `classConstant.deprecatedClass` | — |

### app/bundles/PluginBundle/Tests/Form/Type/IntegrationsListTypeTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 48 | `ignore` | `classConstant.deprecatedClass` | — |
| 54 | `ignore` | `classConstant.deprecatedClass` | — |
| 73 | `ignore` | `classConstant.deprecatedClass` | — |
| 181 | `ignore` | `classConstant.deprecatedClass` | — |
| 187 | `ignore` | `classConstant.deprecatedClass` | — |
| 206 | `ignore` | `classConstant.deprecatedClass` | — |

### app/bundles/PluginBundle/Tests/Integration/AbstractIntegrationTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 20 | `ignore` | `classConstant.deprecatedClass` | — |
| 75 | `ignore` | `classConstant.deprecatedClass` | — |
| 100 | `ignore` | `class.extendsFinalByPhpDoc` | — |

### app/bundles/ReportBundle/Event/ReportGeneratorEvent.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 426 | `ignore-line` | — | — |

### app/bundles/SmsBundle/Tests/Integration/Twilio/ConfigurationTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 27 | `ignore` | `classConstant.deprecatedClass` | — |

### app/bundles/WebhookBundle/Controller/WebhookController.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 34 | `ignore-next-line` | — | FormController extends deprecated AbstractStandardFormController; fix requires class hierarchy refactoring |

### plugins/GrapesJsBuilderBundle/Tests/Unit/Model/GrapesJsBuilderModelTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 81 | `ignore` | `class.extendsFinalByPhpDoc` | — |
| 92 | `ignore-line` | — | — |
| 184 | `ignore` | `class.extendsFinalByPhpDoc` | — |
| 195 | `ignore-line` | — | — |

### plugins/MauticCrmBundle/Integration/SugarcrmIntegration.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 1603 | `ignore-next-line` | — | $item is mixed from untyped $response array; structure is guaranteed by SugarCRM API |
| 1605 | `ignore-next-line` | — | $item is mixed from untyped $response array; structure is guaranteed by SugarCRM API |

### plugins/MauticCrmBundle/Tests/Api/SalesforceApiTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 391 | `ignore-next-line` | — | — |
| 453 | `ignore-next-line` | — | — |

### plugins/MauticCrmBundle/Tests/DynamicsApiTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 38 | `ignore` | `new.resultUnused` | — |

### plugins/MauticSocialBundle/Tests/Integration/FoursquareIntegrationTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 44 | `ignore-next-line` | — | Intentional null check |

### plugins/MauticSocialBundle/Tests/Integration/InstagramIntegrationTest.php

| Line | Annotation | Identifier | Note |
| --- | --- | --- | --- |
| 44 | `ignore-next-line` | — | Intentional null check |
