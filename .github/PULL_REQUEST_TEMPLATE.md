**Please be sure you are submitting this against the _staging_ branch.**

[//]: # ( Please answer the following questions: )

| Q  | A
| --- | ---
| Bug fix? | 
| New feature? | 
| Automated tests included? |
| Related user documentation PR URL | 
| Related developer documentation PR URL | 
| Issues addressed (#s or URLs) | 
| BC breaks? | 
| Deprecations? | 

[//]: # ( Note that all new features should have a related user and/or developer documentation PR in their respective repositories. )

[//]: # ( Required: )
#### Description:

[//]: # ( As applicable: )
#### Steps to reproduce the bug:
1. 
2. 

#### Steps to test this PR:
1. 
2. 

#### Testing this PR requires running the following commands:
Action |  Command  | Required
| --- | --- | ---
clear cache | php app/console cache:clear | 
regenerate assets | php app/console mautic:assets:generate | 
update db schema | run command php app/console doctrine:schema:update --force | 

#### List deprecations along with the new alternative:
1. 
2. 

#### List backwards compatibility breaks:
1. 
2. 
