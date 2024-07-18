<!-- ## Which branch should I use for my PR?

Assuming that:

a = current major release
b = current minor release
c = future major release

* a.x for any features and enhancements (e.g. 5.x)
* a.b for any bug fixes (e.g. 4.4, 5.1)
* c.x for any features, enhancements or bug fixes with backward compatibility breaking changes (e.g. 5.x) -->

| Q                                      | A
| -------------------------------------- | ---
| Bug fix? (use the a.b branch)          | 🔴🟢 <!-- Use emojis to indicate positive (green) or negative (red) for each item in the table. -->
| New feature/enhancement? (use the a.x branch)      | 🔴🟢
| Deprecations?                          | 🔴🟢
| BC breaks? (use the c.x branch)        | 🔴🟢
| Automated tests included?              | 🔴🟢 <!-- All PRs must maintain or improve code coverage -->
| Related user documentation PR URL      | mautic/user-documentation#... <!-- required for new features -->
| Related developer documentation PR URL | mautic/developer-documentation-new#... <!-- required for developer-facing changes -->
| Issue(s) addressed                     | Fixes #... <!-- prefix each issue number with "Fixes #", no need to create an issue if none exists, explain below instead -->

<!--
Additionally (see https://contribute.mautic.org/contributing-to-mautic/developer/code/pull-requests#work-on-your-pull-request):
 - Always add tests and ensure they pass.
 - Bug fixes must be submitted against the lowest maintained branch where they apply
   (lowest branches are regularly merged to upper ones so they get the fixes too.)
 - Features and deprecations must be submitted against the "4.x" branch.
-->

## Description



<!--
Please write a short README for your feature/bugfix. This will help people understand your PR and what it aims to do. If you are fixing a bug and if there is no linked issue already, please provide steps to reproduce the issue here.
-->
<!-- Remove HTML comment markup below to use the table for screenshots when relevant. -->
<!--
| Before                                 | After
| -------------------------------------- | ---
|                                        | 
-->


---
### 📋 Steps to test this PR:

<!--
This part is crucial. Take the time to write very clear, annotated and step by step test instructions, because testers may not be developers.
-->
1. Open this PR on Gitpod or pull down for testing locally (see docs on testing PRs [here](https://contribute.mautic.org/contributing-to-mautic/tester))
2. 

<!--
If you have any deprecations and backwards compatibility breaks, list them here along with the new alternative.
-->