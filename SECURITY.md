# Security Policy

Goals of the Mautic Security Team
---------------------------------

*   Resolve reported security issues in a Security Advisory
*   Provide documentation on how to write secure code
*   Provide documentation on securing your Mautic instance
*   Help the infrastructure team to keep the \*.mautic.org infrastructure secure

Scope of the Mautic Security Team
---------------------------------

The Mautic Security Team operates with a limited scope and only directly responds to issues with Mautic core, officially supported plugins and the \*.mautic.org network of websites. The team does not directly handle potential vulnerabilities with third party plugins or individual Mautic instances.

Which Releases Get Security Advisories?
---------------------------------------

Check the [Releases page](https://www.mautic.org/mautic-releases) to find which are the currently supported releases.

Starting with the release of Mautic 3.0, one minor version at a time receives security advisories, the most recent minor release.

For example, Mautic 4.1 will continue receiving security advisories until the release of Mautic 4.2, and 4.2 will receive security advisories until the release of 4.3.

Security advisories are only made for issues affecting stable releases in the supported major version branches. That means there will be no security advisories for development releases, alphas, betas or release candidates.

How to report a potential security issue
----------------------------------------

If you discover or learn about a potential error, weakness, or threat that can compromise the security of Mautic and is covered by the [Security Advisory Policy](https://www.mautic.org/mautic-security-team/mautic-security-advisory-policy), we ask you to keep it confidential and submit your concern to the Mautic security team.

To make your report please submit it as a private disclosure at [https://github.com/mautic/mautic/security](https://github.com/mautic/mautic/security). You can also create a private fork to provide a fix, if you're able to do so. See the documentation from GitHub on [privately reporting a security issue](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing-information-about-vulnerabilities/privately-reporting-a-security-vulnerability).

Do not post it in GitHub as an issue or a Pull Request, on the forums, or discuss it in Slack.

[Read more: How to report a security issue with Mautic](https://www.mautic.org/mautic-security-team/how-to-report-a-security-issue)

How are security issues resolved?
---------------------------------

The Mautic Security Team are responsible for triaging incoming security issues relating to Mautic core and officially supported plugins, and for releasing fixes in a timely manner.

[Read more: How are security issues triaged and resolved by the Mautic Security Team?](https://www.mautic.org/mautic-security-team/triaging-and-resolving-security-issues)

How are security fixes announced and released?
----------------------------------------------

The Security Team coordinates security announcements in release cycles and evaluates whether security issues are ready for release several days in advance.

The team may deem it necessary to make an out-of-sequence release, in which case at least two weeks’ notice will be provided to ensure that Mautic users are made aware of a security release being made on an unscheduled basis.

[Read more: Security fix announcements and releases](https://www.mautic.org/mautic-security-team/triaging-and-resolving-security-issues)

What is a Security Advisory?
----------------------------

A security advisory is a public announcement managed by the Mautic Security Team which informs Mautic users about a reported security problem in Mautic core or an officially supported plugin and the steps Mautic users should take to address it. (Usually this involves updating to a new release of the code that fixes the security problem.)

[Read more: Mautic Security Advisory Policy](https://www.mautic.org/mautic-security-team/mautic-security-advisory-policy)

What is the disclosure policy of the Mautic Security Team?
----------------------------------------------------------

The security team follows a Coordinated Disclosure policy: we keep issues private until there is a fix. Public announcements are made when the threat has been addressed and a secure version is available.

When reporting a security issue, observe the same policy. **Do not** share your knowledge of security issues with others.

How do I join the Mautic Security Team?
---------------------------------------

As membership in the team gives the individual access to potentially destructive information, membership is limited to people who have a proven track record in the Mautic community.

Team members are expected to work at least a few hours every month. Exceptions to that can be made for short periods to accommodate other priorities, but people who can't maintain some level of involvement will be asked to reconsider their membership on the team.

[Read more: How do I join the Mautic Security Team?](https://www.mautic.org/mautic-security-team/join-the-team)

Who are the Mautic Security Team members?
-----------------------------------------

You can meet the Mautic Security Team on the page below.

[Read more: Meet the Mautic Security Team](https://www.mautic.org/meet-the-mautic-security-team)

Resources and guidance from the [Drupal](https://www.drupal.org/security), [Joomla](https://developer.joomla.org/security.html) and [Mozilla](https://www.mozilla.org/en-US/security/) projects have been drawn from to create these documents and develop our processes/workflows.


Always [report the issue to the team](https://www.mautic.org/mautic-security-team/how-to-report-a-security-issue) and let them make the decision on whether to handle it in public or private.
