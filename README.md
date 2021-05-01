[![codecov](https://codecov.io/gh/mautic/mautic/branch/features/graph/badge.svg)](https://codecov.io/gh/mautic/mautic)
<!-- ALL-CONTRIBUTORS-BADGE:START - Do not remove or modify this section -->
[![All Contributors](https://img.shields.io/badge/all_contributors-3-orange.svg?style=flat-square)](#contributors-)
<!-- ALL-CONTRIBUTORS-BADGE:END -->

[![codecov](https://codecov.io/gh/mautic/mautic/branch/features/graph/badge.svg)](https://codecov.io/gh/mautic/mautic)
<!-- ALL-CONTRIBUTORS-BADGE:START - Do not remove or modify this section -->
[![All Contributors](https://img.shields.io/badge/all_contributors-3-orange.svg?style=flat-square)](#contributors-)
<!-- ALL-CONTRIBUTORS-BADGE:END -->


About Mautic
============
Mautic is the world’s largest open source marketing automation project. With over 200,000 organisations using Mautic and over 1,000 community volunteers, we empower businesses by making it easy to manage their marketing across a range of channels. Stay up to date about initiatives, releases and strategy via our [blog][mautic-blog].

Marketing automation has historically been difficult to implement within organisations. The Mautic Community is an example of open source at its best, offering great software and a vibrant and caring community in which to learn and share knowledge.

Open source means more than open code. Open source provides equality for all and a chance for everyone to improve.

![Mautic](.github/readme_image.png "Mautic Open Source Marketing Automation")

Get Involved
=============
Before we tell you how to install and use Mautic, we like to shamelessly plug our awesome user and developer communities! Users, start [here][get-involved] for inspiration, or follow us on Twitter [@MauticCommunity][twitter] or Facebook [@MauticCommunity][facebook]. Once you’re familiar with using the software, maybe you will share your wisdom with others in our [Slack][slack] channel.

Calling all devs, testers and tech writers! Technical contributions are also welcome. First, read our [general guidelines][contributing] about contributing. If you want to contribute code, read our [CONTRIBUTING.md][contributing-md] or [Contributing Code][contribute-developer] docs then check out the issues with the [T1 label][t1-isssues] to get stuck in quickly and show us what you’re made of.

If you have questions, the Mautic Community can help provide the answers.

Installing and using Mautic
============================

## Supported Versions

| Branch | RC Release | Initial Release | Active Support Until | Security Support Until*
|--|--|--|--|--|
|2.15  | 27 Sep 2019 | 8 Oct 2019 | 8 Oct 2019 | 8 Oct 2019
|2.16  | 30 Jan 2020 | 13 Feb 2020 | 15 June 2020 | 15 December 2020
|3.x   | 27 Jan 2020 | 15 June 2020 | 15 June 2021 | 15 December 2021
|3.1   | 17 Aug 2020 | 24 Aug 2020 | 23 Nov 2020 | 30 Nov 2020
|3.2   | 23 Nov 2020 | 30 Nov 2020 | 16 Feb 2021 | 22 Feb 2021
|3.3   | 16 Feb 2021 | 22 Feb 2021 | 17 May 2021 | 24 May 2021
|4.x   | 17 May 2021 | 24 May 2021 | 24 May 2022 | 20 Dec 2022

`*`Security support for 2.16 will only be provided for Mautic itself, not for core dependencies that are EOL, such as Symfony 2.8.

## Software Downloads
The GitHub version is recommended for both development and testing. The production package (including all libraries) is available at [mautic.org/download][download-mautic].

## Installation
### Disclaimer
*Install from source only if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and keep it working. If the source/database schema gets out of sync with Mautic releases, the release updater may not work and will require manual updates. For production, we recommend the pre-packaged Mautic which is available at [mautic.org/download][download-mautic].*

*Also note that source code outside of a [tagged release][tagged-release] should be considered ‘alpha’. It may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.*

### How to install Mautic
You must already have [Composer v1][composer-v1] available on your computer because this is a development release and you'll need Composer to download the vendor packages. Note that Composer v2 is not yet supported.

Also note that if you have DDEV installed, you can run 'ddev config' followed by 'ddev start'. This will kick off the Mautic first-run process which will automatically install dependencies and configure Mautic for use. ✨ 🚀 Read more [here][ddev-mautic]

Installing Mautic is a simple three-step process:

1. [Download the repository zip][download-zip] then extract the zip to your web root.
2. Run the `composer install` command to install the required packages.
3. Open your browser and complete the installation through the web installer.

If you get stuck, check our our [general troubleshooting][troubleshooting] page. Still no joy? Join our lively [Mautic Community][community] for support and answers.

### User Documentation
Documentation on how to use Mautic is available at [docs.mautic.org][mautic-docs].

### Developer Docs
Developer documentation, including API reference docs, is available at [developer.mautic.org][dev-docs].


## Contributors ✨

Thanks goes to these wonderful people ([emoji key](https://allcontributors.org/docs/en/emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tr>
    <td align="center"><a href="https://twitter.com/dennisameling"><img src="https://avatars.githubusercontent.com/u/17739158?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Dennis Ameling</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=dennisameling" title="Code">💻</a> <a href="#userTesting-dennisameling" title="User Testing">📓</a></td>
    <td align="center"><a href="https://steercampaign.com"><img src="https://avatars.githubusercontent.com/u/12627658?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Mohammad Abu Musa</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=mabumusa1" title="Code">💻</a></td>
    <td align="center"><a href="http://johnlinhart.com"><img src="https://avatars.githubusercontent.com/u/1235442?v=4?s=100" width="100px;" alt=""/><br /><sub><b>John Linhart</b></sub></a><br /><a href="#userTesting-escopecz" title="User Testing">📓</a></td>
  </tr>
</table>

<!-- markdownlint-restore -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors][all-contributors] specification. Contributions of any kind welcome!

[mautic-blog]: <https://www.mautic.org/blog>
[get-involved]: <https://www.mautic.org/community/get-involved>
[twitter]: <https://twitter.com/MauticCommunity>
[facebook]: <https://www.facebook.com/MauticCommunity/>
[slack]: <https://www.mautic.org/community/get-involved/communication-channels>
[contributing]: <https://contribute.mautic.org/contributing-to-mautic>
[contributing-md]: <https://github.com/mautic/mautic/blob/feature/.github/CONTRIBUTING.md>
[contribute-developer]: <https://contribute.mautic.org/contributing-to-mautic/developer>
[t1-issues]: <https://github.com/mautic/mautic/issues?q=is%3Aissue+is%3Aopen+label%3AT1>
[download-mautic]: <https://www.mautic.org/download>
[tagged-release]: <https://github.com/mautic/mautic/releases>
[composer-v1]: <http://getcomposer.org/>
[download-zip]: <https://github.com/mautic/mautic/archive/refs/heads/features.zip>
[ddev-mautic]: <https://kb.mautic.org/knowledgebase/development/how-to-install-mautic-using-ddev>
[troubleshooting]: <https://docs.mautic.org/en/troubleshooting>
[community]: <https://www.mautic.org/community>
[mautic-docs]: <https://docs.mautic.org>
[dev-docs]: <https://developer.mautic.org>
[all-contributors]: <https://github.com/all-contributors/all-contributors>
