[![codecov](https://codecov.io/gh/mautic/mautic/branch/features/graph/badge.svg)](https://codecov.io/gh/mautic/mautic)
<!-- ALL-CONTRIBUTORS-BADGE:START - Do not remove or modify this section -->
[![All Contributors](https://img.shields.io/badge/all_contributors-82-orange.svg?style=flat-square)](#contributors-)
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

Calling all devs, testers and tech writers! Technical contributions are also welcome. First, read our [general guidelines][contributing] about contributing. If you want to contribute code, read our [CONTRIBUTING.md][contributing-md] or [Contributing Code][contribute-developer] docs then check out the issues with the [T1 label][t1-issues] to get stuck in quickly and show us what you’re made of.

If you have questions, the Mautic Community can help provide the answers.

Installing and using Mautic
============================

Please check the latest supported versions on the [Mautic Releases](https://www.mautic.org/mautic-releases) page.

## Software Downloads
The GitHub version is recommended for both development and testing. The production package (including all libraries) is available at [mautic.org/download][download-mautic].

## Installation
### Disclaimer
*Install from source only if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and keep it working. If the source/database schema gets out of sync with Mautic releases, the release updater may not work and will require manual updates. For production, we recommend the pre-packaged Mautic which is available at [mautic.org/download][download-mautic].*

*Also note that source code outside of a [tagged release][tagged-release] should be considered ‘alpha’. It may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.*

### How to install Mautic
You must already have [Composer][composer] available on your computer because this is a development release and you'll need Composer to download the vendor packages.

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
    <td align="center"><a href="https://steercampaign.com"><img src="https://avatars.githubusercontent.com/u/12627658?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Mohammad Abu Musa</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=mabumusa1" title="Code">💻</a> <a href="#userTesting-mabumusa1" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Amabumusa1" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="http://johnlinhart.com"><img src="https://avatars.githubusercontent.com/u/1235442?v=4?s=100" width="100px;" alt=""/><br /><sub><b>John Linhart</b></sub></a><br /><a href="#userTesting-escopecz" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Aescopecz" title="Reviewed Pull Requests">👀</a> <a href="https://github.com/mautic/mautic/commits?author=escopecz" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=escopecz" title="Tests">⚠️</a></td>
    <td align="center"><a href="https://www.webmecanik.com"><img src="https://avatars.githubusercontent.com/u/14075239?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Norman Pracht - Webmecanik</b></sub></a><br /><a href="#userTesting-npracht" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/commits?author=npracht" title="Code">💻</a></td>
    <td align="center"><a href="https://webmecanik.com"><img src="https://avatars.githubusercontent.com/u/462477?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Zdeno Kuzmany</b></sub></a><br /><a href="#userTesting-kuzmany" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Akuzmany" title="Reviewed Pull Requests">👀</a> <a href="https://github.com/mautic/mautic/commits?author=kuzmany" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=kuzmany" title="Tests">⚠️</a></td>
    <td align="center"><a href="https://github.com/stevedrobinson"><img src="https://avatars.githubusercontent.com/u/866855?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Steve Robinson</b></sub></a><br /><a href="#userTesting-stevedrobinson" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/issues?q=author%3Astevedrobinson" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://github.com/snoblucha"><img src="https://avatars.githubusercontent.com/u/265586?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Petr Šnobl</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=snoblucha" title="Code">💻</a> <a href="https://github.com/mautic/mautic/issues?q=author%3Asnoblucha" title="Bug reports">🐛</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://github.com/luguenth"><img src="https://avatars.githubusercontent.com/u/9964009?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Lukas Günther</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=luguenth" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=luguenth" title="Documentation">📖</a> <a href="#userTesting-luguenth" title="User Testing">📓</a></td>
    <td align="center"><a href="https://www.ruthcheesley.co.uk"><img src="https://avatars.githubusercontent.com/u/2930593?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Ruth Cheesley</b></sub></a><br /><a href="#userTesting-rcheesley" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Archeesley" title="Reviewed Pull Requests">👀</a> <a href="https://github.com/mautic/mautic/commits?author=rcheesley" title="Documentation">📖</a></td>
    <td align="center"><a href="https://github.com/anton-vlasenko"><img src="https://avatars.githubusercontent.com/u/43744263?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Anton Vlasenko</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=anton-vlasenko" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=anton-vlasenko" title="Tests">⚠️</a></td>
    <td align="center"><a href="https://www.linkedin.com/in/miroslavfedeles"><img src="https://avatars.githubusercontent.com/u/6388925?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Miroslav Fedeleš</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=fedys" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=fedys" title="Tests">⚠️</a> <a href="#userTesting-fedys" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Afedys" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="https://github.com/gabepri"><img src="https://avatars.githubusercontent.com/u/73728034?v=4?s=100" width="100px;" alt=""/><br /><sub><b>gabepri</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Agabepri" title="Bug reports">🐛</a> <a href="https://github.com/mautic/mautic/commits?author=gabepri" title="Code">💻</a></td>
    <td align="center"><a href="https://incentfit.com"><img src="https://avatars.githubusercontent.com/u/13243272?v=4?s=100" width="100px;" alt=""/><br /><sub><b>incentfit</b></sub></a><br /><a href="#userTesting-incentfit" title="User Testing">📓</a></td>
    <td align="center"><a href="http://drahy.net"><img src="https://avatars.githubusercontent.com/u/12815758?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Lukáš Drahý</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=hluchas" title="Code">💻</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Ahluchas" title="Reviewed Pull Requests">👀</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://about.me/alanhartless"><img src="https://avatars.githubusercontent.com/u/63312?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Alan Hartless (he/him)</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=alanhartless" title="Code">💻</a></td>
    <td align="center"><a href="http://mohitaghera.in"><img src="https://avatars.githubusercontent.com/u/2618452?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Mohit Aghera</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=mohit-rocks" title="Code">💻</a> <a href="#userTesting-mohit-rocks" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Amohit-rocks" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="https://github.com/domparry"><img src="https://avatars.githubusercontent.com/u/19376765?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Dom Parry</b></sub></a><br /><a href="#userTesting-domparry" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/sensalot"><img src="https://avatars.githubusercontent.com/u/6697244?v=4?s=100" width="100px;" alt=""/><br /><sub><b>sensalot</b></sub></a><br /><a href="#userTesting-sensalot" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/shinde-rahul"><img src="https://avatars.githubusercontent.com/u/1046788?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Rahul Shinde</b></sub></a><br /><a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Ashinde-rahul" title="Reviewed Pull Requests">👀</a> <a href="#userTesting-shinde-rahul" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/commits?author=shinde-rahul" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/jos0405"><img src="https://avatars.githubusercontent.com/u/4246909?v=4?s=100" width="100px;" alt=""/><br /><sub><b>jos0405</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=jos0405" title="Code">💻</a> <a href="#userTesting-jos0405" title="User Testing">📓</a></td>
    <td align="center"><a href="http://veenhof.be"><img src="https://avatars.githubusercontent.com/u/161341?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Nick Veenhof</b></sub></a><br /><a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Anickveenhof" title="Reviewed Pull Requests">👀</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://github.com/patrykgruszka"><img src="https://avatars.githubusercontent.com/u/8580942?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Patryk Gruszka</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=patrykgruszka" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=patrykgruszka" title="Documentation">📖</a> <a href="https://github.com/mautic/mautic/commits?author=patrykgruszka" title="Tests">⚠️</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Apatrykgruszka" title="Reviewed Pull Requests">👀</a> <a href="#userTesting-patrykgruszka" title="User Testing">📓</a></td>
    <td align="center"><a href="https://hartmut.io"><img src="https://avatars.githubusercontent.com/u/20030306?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Alex Hammerschmied</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=alexhammerschmied" title="Code">💻</a></td>
    <td align="center"><a href="https://www.twentyzen.com"><img src="https://avatars.githubusercontent.com/u/1241376?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Dirk Spannaus</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Adsp76" title="Bug reports">🐛</a> <a href="#userTesting-dsp76" title="User Testing">📓</a></td>
    <td align="center"><a href="http://www.linkedin.com/in/rehannischal"><img src="https://avatars.githubusercontent.com/u/43839944?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Rehan Nischal</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3ARehanNischal" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://github.com/Christophe9880"><img src="https://avatars.githubusercontent.com/u/82932885?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Christophe9880</b></sub></a><br /><a href="#userTesting-Christophe9880" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/dadarya0"><img src="https://avatars.githubusercontent.com/u/48244990?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Saurabh Gupta</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=dadarya0" title="Code">💻</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Adadarya0" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="https://github.com/ts-navghane"><img src="https://avatars.githubusercontent.com/u/54406786?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Tejas Navghane</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=ts-navghane" title="Tests">⚠️</a> <a href="https://github.com/mautic/mautic/commits?author=ts-navghane" title="Code">💻</a> <a href="#userTesting-ts-navghane" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Ats-navghane" title="Reviewed Pull Requests">👀</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://www.webmecanik.com"><img src="https://avatars.githubusercontent.com/u/49391402?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Florent Petitjean - Webmecanik</b></sub></a><br /><a href="#userTesting-florentpetitjean" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/tobsowo"><img src="https://avatars.githubusercontent.com/u/5642737?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Oluwatobi Owolabi</b></sub></a><br /><a href="#eventOrganizing-tobsowo" title="Event Organizing">📋</a></td>
    <td align="center"><a href="https://www.linkedin.com/in/favour-kelvin/"><img src="https://avatars.githubusercontent.com/u/39309699?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Favour Kelvin</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=fakela" title="Documentation">📖</a> <a href="#tutorial-fakela" title="Tutorials">✅</a> <a href="#talk-fakela" title="Talks">📢</a></td>
    <td align="center"><a href="http://poisson.phc.dm.unipi.it/~mascellani"><img src="https://avatars.githubusercontent.com/u/101675?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Giovanni Mascellani</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=giomasce" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/RaphaelWoude"><img src="https://avatars.githubusercontent.com/u/47354694?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Raphael van der Woude</b></sub></a><br /><a href="#userTesting-RaphaelWoude" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/mannp"><img src="https://avatars.githubusercontent.com/u/4335298?v=4?s=100" width="100px;" alt=""/><br /><sub><b>mannp</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Amannp" title="Bug reports">🐛</a> <a href="#userTesting-mannp" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/MarketSmart"><img src="https://avatars.githubusercontent.com/u/85239715?v=4?s=100" width="100px;" alt=""/><br /><sub><b>MarketSmart</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=MarketSmart" title="Code">💻</a></td>
  </tr>
  <tr>
    <td align="center"><a href="http://www.leuchtfeuer.com"><img src="https://avatars.githubusercontent.com/u/55587275?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Leon</b></sub></a><br /><a href="#userTesting-oltmanns-leuchtfeuer" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/bryanitamazonva"><img src="https://avatars.githubusercontent.com/u/79956709?v=4?s=100" width="100px;" alt=""/><br /><sub><b>bryanitamazonva</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Abryanitamazonva" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://github.com/n-azimy"><img src="https://avatars.githubusercontent.com/u/86242419?v=4?s=100" width="100px;" alt=""/><br /><sub><b>n-azimy</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=n-azimy" title="Code">💻</a></td>
    <td align="center"><a href="https://bandism.net/"><img src="https://avatars.githubusercontent.com/u/22633385?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Ikko Ashimine</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=eltociear" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/maxlawton"><img src="https://avatars.githubusercontent.com/u/1194823?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Max Lawton</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=maxlawton" title="Code">💻</a> <a href="https://github.com/mautic/mautic/commits?author=maxlawton" title="Documentation">📖</a></td>
    <td align="center"><a href="https://github.com/rohitpavaskar"><img src="https://avatars.githubusercontent.com/u/15215575?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Rohit Pavaskar</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=rohitpavaskar" title="Code">💻</a></td>
    <td align="center"><a href="https://www.udemy.com/certificate/UC-5CZA2NJ8/"><img src="https://avatars.githubusercontent.com/u/22201881?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Disha P</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=disha-pishavadia24" title="Code">💻</a></td>
  </tr>
  <tr>
    <td align="center"><a href="http://www.idea2.ch"><img src="https://avatars.githubusercontent.com/u/13075514?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Adrian</b></sub></a><br /><a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Aadiux" title="Reviewed Pull Requests">👀</a> <a href="#userTesting-adiux" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/commits?author=adiux" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/vijayhrdm"><img src="https://avatars.githubusercontent.com/u/9714242?v=4?s=100" width="100px;" alt=""/><br /><sub><b>vijayhrdm</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Avijayhrdm" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://github.com/julienWebmecanik"><img src="https://avatars.githubusercontent.com/u/79137416?v=4?s=100" width="100px;" alt=""/><br /><sub><b>julienWebmecanik</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=julienWebmecanik" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/johbuch"><img src="https://avatars.githubusercontent.com/u/31535432?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Johan Buchert</b></sub></a><br /><a href="#userTesting-johbuch" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/TS16V"><img src="https://avatars.githubusercontent.com/u/38064792?v=4?s=100" width="100px;" alt=""/><br /><sub><b>TS16V</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3ATS16V" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://github.com/rafalsk"><img src="https://avatars.githubusercontent.com/u/9338163?v=4?s=100" width="100px;" alt=""/><br /><sub><b>rafalsk</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Arafalsk" title="Bug reports">🐛</a></td>
    <td align="center"><a href="https://jonathanphoto.fr"><img src="https://avatars.githubusercontent.com/u/55917666?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Jonathan Dahan</b></sub></a><br /><a href="https://github.com/mautic/mautic/issues?q=author%3Ajonathandhn" title="Bug reports">🐛</a></td>
  </tr>
  <tr>
    <td align="center"><a href="http://twitter.com/j26w"><img src="https://avatars.githubusercontent.com/u/1260184?v=4?s=100" width="100px;" alt=""/><br /><sub><b>j26w</b></sub></a><br /><a href="#userTesting-j26w" title="User Testing">📓</a></td>
    <td align="center"><a href="http://theodorosploumis.com/en"><img src="https://avatars.githubusercontent.com/u/1315321?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Theodoros Ploumis</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=theodorosploumis" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/sreenia806"><img src="https://avatars.githubusercontent.com/u/2764179?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Sreenivasulu Avula</b></sub></a><br /><a href="#userTesting-sreenia806" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Asreenia806" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="https://www.linkedin.com/in/mohammadlahlouh/"><img src="https://avatars.githubusercontent.com/u/7312050?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Mohammad Lahlouh</b></sub></a><br /><a href="#userTesting-mlahlouh" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/ArnaudSau"><img src="https://avatars.githubusercontent.com/u/50580844?v=4?s=100" width="100px;" alt=""/><br /><sub><b>ArnaudSau</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=ArnaudSau" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/bell87"><img src="https://avatars.githubusercontent.com/u/5338785?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Andrew Bell</b></sub></a><br /><a href="#userTesting-bell87" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/alfredoct96"><img src="https://avatars.githubusercontent.com/u/50916237?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Alfredo Arena</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=alfredoct96" title="Code">💻</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://github.com/mollux"><img src="https://avatars.githubusercontent.com/u/3983285?v=4?s=100" width="100px;" alt=""/><br /><sub><b>mollux</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=mollux" title="Code">💻</a></td>
    <td align="center"><a href="http://Leuchtfeuer.com"><img src="https://avatars.githubusercontent.com/u/43146234?v=4?s=100" width="100px;" alt=""/><br /><sub><b>ekkeguembel</b></sub></a><br /><a href="#userTesting-ekkeguembel" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/MadlenF"><img src="https://avatars.githubusercontent.com/u/87804194?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Madlen</b></sub></a><br /><a href="#userTesting-MadlenF" title="User Testing">📓</a></td>
    <td align="center"><a href="https://friendly.ch/kathrin"><img src="https://avatars.githubusercontent.com/u/96054002?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Kathrin Schmid</b></sub></a><br /><a href="#translation-kathrin-schmid" title="Translation">🌍</a></td>
    <td align="center"><a href="https://github.com/rahuld-dev"><img src="https://avatars.githubusercontent.com/u/68939488?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Rahul Dhande</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=rahuld-dev" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/biozshock"><img src="https://avatars.githubusercontent.com/u/169384?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Artem Lopata</b></sub></a><br /><a href="#userTesting-biozshock" title="User Testing">📓</a> <a href="https://github.com/mautic/mautic/pulls?q=is%3Apr+reviewed-by%3Abiozshock" title="Reviewed Pull Requests">👀</a></td>
    <td align="center"><a href="https://github.com/abailey-dev"><img src="https://avatars.githubusercontent.com/u/65302481?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Anthony Bailey</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=abailey-dev" title="Code">💻</a></td>
  </tr>
  <tr>
    <td align="center"><a href="http://twitter.com/eloimarques"><img src="https://avatars.githubusercontent.com/u/11034410?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Eloi Marques da Silva</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=eloimarquessilva" title="Code">💻</a></td>
    <td align="center"><a href="http://adevo.pl"><img src="https://avatars.githubusercontent.com/u/39382654?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Tomasz Kowalczyk</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=tomekkowalczyk" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/jensolsson"><img src="https://avatars.githubusercontent.com/u/1985582?v=4?s=100" width="100px;" alt=""/><br /><sub><b>jensolsson</b></sub></a><br /><a href="#userTesting-jensolsson" title="User Testing">📓</a></td>
    <td align="center"><a href="http://tonybogdanov.com"><img src="https://avatars.githubusercontent.com/u/3586948?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Tony Bogdanov</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=TonyBogdanov" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/bradycargle"><img src="https://avatars.githubusercontent.com/u/79949869?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Brady Cargle</b></sub></a><br /><a href="#userTesting-bradycargle" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/mmarton"><img src="https://avatars.githubusercontent.com/u/1424582?v=4?s=100" width="100px;" alt=""/><br /><sub><b>mmarton</b></sub></a><br /><a href="#userTesting-mmarton" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/bodrak"><img src="https://avatars.githubusercontent.com/u/3704648?v=4?s=100" width="100px;" alt=""/><br /><sub><b>bodrak</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=bodrak" title="Code">💻</a></td>
  </tr>
  <tr>
    <td align="center"><a href="https://github.com/nick-vanpraet"><img src="https://avatars.githubusercontent.com/u/7923739?v=4?s=100" width="100px;" alt=""/><br /><sub><b>nick-vanpraet</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=nick-vanpraet" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/volha-pivavarchyk"><img src="https://avatars.githubusercontent.com/u/96085911?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Volha Pivavarchyk</b></sub></a><br /><a href="#userTesting-volha-pivavarchyk" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/J-Light"><img src="https://avatars.githubusercontent.com/u/2544660?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Nish Joseph</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=J-Light" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/Damzoneuh"><img src="https://avatars.githubusercontent.com/u/44919863?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Damzoneuh</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=Damzoneuh" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/alinmbb"><img src="https://avatars.githubusercontent.com/u/86683952?v=4?s=100" width="100px;" alt=""/><br /><sub><b>alinmbb</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=alinmbb" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/deborahsalves"><img src="https://avatars.githubusercontent.com/u/79517214?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Déborah Salves</b></sub></a><br /><a href="#userTesting-deborahsalves" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/automatyzuj"><img src="https://avatars.githubusercontent.com/u/104569506?v=4?s=100" width="100px;" alt=""/><br /><sub><b>automatyzuj</b></sub></a><br /><a href="#userTesting-automatyzuj" title="User Testing">📓</a></td>
  </tr>
  <tr>
    <td align="center"><a href="http://benjamin.leveque.me"><img src="https://avatars.githubusercontent.com/u/166890?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Benjamin Lévêque</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=benji07" title="Code">💻</a></td>
    <td align="center"><a href="https://buzelac.com"><img src="https://avatars.githubusercontent.com/u/430255?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Benjamin</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=uzegonemad" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/IrisAmrein"><img src="https://avatars.githubusercontent.com/u/70972871?v=4?s=100" width="100px;" alt=""/><br /><sub><b>Iris Amrein</b></sub></a><br /><a href="#userTesting-IrisAmrein" title="User Testing">📓</a></td>
    <td align="center"><a href="https://github.com/pety-dc"><img src="https://avatars.githubusercontent.com/u/25766885?v=4?s=100" width="100px;" alt=""/><br /><sub><b>peter.osvath</b></sub></a><br /><a href="https://github.com/mautic/mautic/commits?author=pety-dc" title="Code">💻</a></td>
    <td align="center"><a href="https://github.com/poweriguana"><img src="https://avatars.githubusercontent.com/u/86078621?v=4?s=100" width="100px;" alt=""/><br /><sub><b>poweriguana</b></sub></a><br /><a href="#userTesting-poweriguana" title="User Testing">📓</a> <a href="#projectManagement-poweriguana" title="Project Management">📆</a></td>
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
[composer]: <http://getcomposer.org/>
[download-zip]: <https://github.com/mautic/mautic/archive/refs/heads/features.zip>
[ddev-mautic]: <https://kb.mautic.org/knowledgebase/development/how-to-install-mautic-using-ddev>
[troubleshooting]: <https://docs.mautic.org/en/troubleshooting>
[community]: <https://www.mautic.org/community>
[mautic-docs]: <https://docs.mautic.org>
[dev-docs]: <https://developer.mautic.org>
[all-contributors]: <https://github.com/all-contributors/all-contributors>
