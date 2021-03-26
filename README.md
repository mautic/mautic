[![codecov](https://codecov.io/gh/mautic/mautic/branch/features/graph/badge.svg)](https://codecov.io/gh/mautic/mautic)

Mautic Introduction
===========
![Mautic](.github/readme_image.png "Mautic Open Source Marketing Automation")

## Supported Versions

| Branch | RC Release | Initial Release | Active Support Until | Security Support Until *
|--|--|--|--|--|
|2.15  | 27 Sep 2019 | 8 Oct 2019 | 8 Oct 2019 | 8 Oct 2019
|2.16  | 30 Jan 2020 | 13 Feb 2020 | 15 June 2020 | 15 December 2020
|3.x   | 27 Jan 2020 | 15 June 2020 | 15 June 2021 | 15 December 2021
|3.1   | 17 Aug 2020 | 24 Aug 2020 | 23 Nov 2020 | 30 Nov 2020
|3.2   | 23 Nov 2020 | 30 Nov 2020 | 16 Feb 2021 | 22 Feb 2021
|3.3   | 16 Feb 2021 | 22 Feb 2021 | 17 May 2021 | 24 May 2021
|4.x   | 17 May 2021 | 24 May 2021 | 24 May 2022 | 20 Dec 2022

* = Security Support for 2.16 will only be provided for Mautic itself, not for core dependencies that are EOL like Symfony 2.8.

## Getting Started

The GitHub version is recommended for development or testing. Production package ready for install with all the libraries is at [https://www.mautic.org/download](https://www.mautic.org/download).

Documentation on how to use Mautic is available at [https://docs.mautic.org](https://docs.mautic.org).

This is a simple 3 step installation process. You'll want to make sure you already have [Composer v1](http://getcomposer.org) available on your computer as this is a development release and you'll need to use Composer to download the vendor packages. Note that v2 is not yet supported.

<table width="100%" border="0">
	<tr>
		<td>
			<center><b>Step 1</b></center>
		</td>
		<td>
			<center><b>Step 2</b></center>
		</td>
		<td>
			<center><b>Step 3</b></center>
		</td>
	</tr>
	<tr>
		<td align="center" width="33.3%">
			<a href="https://github.com/mautic/mautic/archive/master.zip">Download the repository zip</a><br />Extract this zip to your web root.
		</td>
		<td align="center" width="33.3%">
			Run the following command to install required packages.<br /> <code>composer install</code>
		</td>
		<td align="center" width="33.3%">
			Open your browser and complete the installation through the web installer.
		</td>
	</tr>
</table>

**Get stuck?** *No problem. Check out [general troubleshooting](https://docs.mautic.org/en/troubleshooting) and if it won't solve your issue join us at the <a href="https://www.mautic.org/community">Mautic community</a> for help and answers.*

## Disclaimer
Installing from source is only recommended if you are comfortable using the command line. You'll be required to use various CLI commands to get Mautic working and to keep it working. If the source and/or database schema gets out of sync with Mautic's releases, the release updater may not work and will require manual updates. For production the pre-packaged Mautic available at [mautic.org/download](https://www.mautic.org/download) is recommended.

*Also note that the source outside <a href="https://github.com/mautic/mautic/releases">a tagged release</a> should be considered "alpha" and may contain bugs, cause unexpected results, data corruption or loss, and is not recommended for use in a production environment. Use at your own risk.*

## Ready to Install from Source and/or Contribute?
That's fantastic! 

If you want to contribute to Mautic's **code**, please read our [CONTRIBUTING.md](https://github.com/mautic/mautic/blob/feature/.github/CONTRIBUTING.md) or [Contributing Code](https://contribute.mautic.org/contributing-to-mautic/developer) docs. Then, check out the issues with the [L1 label](https://github.com/mautic/mautic/issues?q=is%3Aissue+is%3Aopen+label%3AL1) to get started quickly :rocket:

If you want to contribute in **other areas** of Mautic, please read our general [Contributing](https://contribute.mautic.org/contributing-to-mautic) guide.

## FAQ and Contact Information
Marketing automation has historically been a difficult tool to implement in a business. The Mautic community is a rich environment for you to learn from others and share your knowledge as well. Open source means more than open code. Open source is providing equality for all and a chance to improve. If you have questions then the Mautic community can help provide the answers.

**Ready to get started with the community?** You can get <a href="https://www.mautic.org/community/get-involved">more involved</a> on the <a href="https://www.mautic.org">Mautic</a> website. Or follow Mautic on social media just to stay current with what's happening!

### Contact Info

* <a href="https://www.mautic.org">https://www.mautic.org</a>
* <a href="https://twitter.com/MauticCommunity">@MauticCommunity</a> [Twitter]
* <a href="https://www.facebook.com/MauticCommunity/">@MauticCommunity</a> [Facebook]
