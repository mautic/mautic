Changelog
=========

1.7.0
-----

- Add support for HHVM and PHP 7

  Requests is now tested against both HHVM and PHP 7, and they are supported as
  first-party platforms.

  (props @rmccue, [#106][gh-106], [#176][gh-176])

- Transfer & connect timeouts, in seconds & milliseconds

  cURL is unable to handle timeouts under a second in DNS lookups, so we round
  those up to ensure 1-999ms isn't counted as an instant failure.

  (props @ozh, @rmccue, [#97][gh-97], [#216][gh-216])

- Rework cookie handling to be more thorough.

  Cookies are now restricted to the same-origin by default, expiration is checked.

  (props @catharsisjelly, @rmccue, [#120][gh-120], [#124][gh-124], [#130][gh-130], [#132][gh-132], [#156][gh-156])

- Improve testing

  Tests are now run locally to speed them up, as well as further general
  improvements to the quality of the testing suite. There are now also
  comprehensive proxy tests to ensure coverage there.

  (props @rmccue, [#75][gh-75], [#107][gh-107], [#170][gh-170], [#177][gh-177], [#181][gh-181], [#183][gh-183], [#185][gh-185], [#196][gh-196], [#202][gh-202], [#203][gh-203])

- Support custom HTTP methods

  Previously, custom HTTP methods were only supported on sockets; they are now
  supported across all transports.

  (props @ocean90, [#227][gh-227])

- Add byte limit option

  (props @rmccue, [#172][gh-172])

- Support a Requests_Proxy_HTTP() instance for the proxy setting.

  (props @ocean90, [#223][gh-223])

- Add progress hook

  (props @rmccue, [#180][gh-180])

- Add a before_redirect hook to alter redirects

  (props @rmccue, [#205][gh-205])

- Pass cURL info to after_request

  (props @rmccue, [#206][gh-206])

- Remove explicit autoload in Composer installation instructions

  (props @SlikNL, [#86][gh-86])

- Restrict CURLOPT_PROTOCOLS on `defined()` instead of `version_compare()`

  (props @ozh, [#92][gh-92])

- Fix doc - typo in "Authentication"

  (props @remik, [#99][gh-99])

- Contextually check for a valid transport

  (props @ozh, [#101][gh-101])

- Follow relative redirects correctly

  (props @ozh, [#103][gh-103])

- Use cURL's version_number

  (props @mishan, [#104][gh-104])

- Removed duplicated option docs

  (props @staabm, [#112][gh-112])

- code styling fixed

  (props @imsaintx, [#113][gh-113])

- Fix IRI "normalization"

  (props @ozh, [#128][gh-128])

- Mention two PHP extension dependencies in the README.

  (props @orlitzky, [#136][gh-136])

- Ignore coverage report files

  (props @ozh, [#148][gh-148])

- drop obsolete "return" after throw

  (props @staabm, [#150][gh-150])

- Updated exception message to specify both http + https

  (props @beutnagel, [#162][gh-162])

- Sets `stream_headers` method to public to allow calling it from other
places.

  (props @adri, [#158][gh-158])

- Remove duplicated stream_get_meta_data call

  (props @rmccue, [#179][gh-179])

- Transmits $errno from stream_socket_client in exception

  (props @laurentmartelli, [#174][gh-174])

- Correct methods to use snake_case

  (props @rmccue, [#184][gh-184])

- Improve code quality

  (props @rmccue, [#186][gh-186])

- Update Build Status image

  (props @rmccue, [#187][gh-187])

- Fix/Rationalize transports (v2)

  (props @rmccue, [#188][gh-188])

- Surface cURL errors

  (props @ifwe, [#194][gh-194])

- Fix for memleak and curl_close() never being called

  (props @kwuerl, [#200][gh-200])

- addex how to install with composer

  (props @royopa, [#164][gh-164])

- Uppercase the method to ensure compatibility

  (props @rmccue, [#207][gh-207])

- Store default certificate path

  (props @rmccue, [#210][gh-210])

- Force closing keep-alive connections on old cURL

  (props @rmccue, [#211][gh-211])

- Docs: Updated HTTP links with HTTPS links where applicable

  (props @ntwb, [#215][gh-215])

- Remove the executable bit

  (props @ocean90, [#224][gh-224])

- Change more links to HTTPS

  (props @rmccue, [#217][gh-217])

- Bail from cURL when either `curl_init()` OR `curl_exec()` are unavailable

  (props @dd32, [#230][gh-230])

- Disable OpenSSL's internal peer_name checking when `verifyname` is disabled.

  (props @dd32, [#239][gh-239])

- Only include the port number in the `Host` header when it differs from
default

  (props @dd32, [#238][gh-238])

- Respect port if specified for HTTPS connections

  (props @dd32, [#237][gh-237])

- Allow paths starting with a double-slash

  (props @rmccue, [#240][gh-240])

- Fixes bug in rfc2616 #3.6.1 implementation.

  (props @stephenharris, [#236][gh-236], [#3][gh-3])

- CURLOPT_HTTPHEADER在php7接受空数组导致php-fpm奔溃

  (props @qibinghua, [#219][gh-219])

[gh-3]: https://github.com/rmccue/Requests/issues/3
[gh-75]: https://github.com/rmccue/Requests/issues/75
[gh-86]: https://github.com/rmccue/Requests/issues/86
[gh-92]: https://github.com/rmccue/Requests/issues/92
[gh-97]: https://github.com/rmccue/Requests/issues/97
[gh-99]: https://github.com/rmccue/Requests/issues/99
[gh-101]: https://github.com/rmccue/Requests/issues/101
[gh-103]: https://github.com/rmccue/Requests/issues/103
[gh-104]: https://github.com/rmccue/Requests/issues/104
[gh-106]: https://github.com/rmccue/Requests/issues/106
[gh-107]: https://github.com/rmccue/Requests/issues/107
[gh-112]: https://github.com/rmccue/Requests/issues/112
[gh-113]: https://github.com/rmccue/Requests/issues/113
[gh-120]: https://github.com/rmccue/Requests/issues/120
[gh-124]: https://github.com/rmccue/Requests/issues/124
[gh-128]: https://github.com/rmccue/Requests/issues/128
[gh-130]: https://github.com/rmccue/Requests/issues/130
[gh-132]: https://github.com/rmccue/Requests/issues/132
[gh-136]: https://github.com/rmccue/Requests/issues/136
[gh-148]: https://github.com/rmccue/Requests/issues/148
[gh-150]: https://github.com/rmccue/Requests/issues/150
[gh-156]: https://github.com/rmccue/Requests/issues/156
[gh-158]: https://github.com/rmccue/Requests/issues/158
[gh-162]: https://github.com/rmccue/Requests/issues/162
[gh-164]: https://github.com/rmccue/Requests/issues/164
[gh-170]: https://github.com/rmccue/Requests/issues/170
[gh-172]: https://github.com/rmccue/Requests/issues/172
[gh-174]: https://github.com/rmccue/Requests/issues/174
[gh-176]: https://github.com/rmccue/Requests/issues/176
[gh-177]: https://github.com/rmccue/Requests/issues/177
[gh-179]: https://github.com/rmccue/Requests/issues/179
[gh-180]: https://github.com/rmccue/Requests/issues/180
[gh-181]: https://github.com/rmccue/Requests/issues/181
[gh-183]: https://github.com/rmccue/Requests/issues/183
[gh-184]: https://github.com/rmccue/Requests/issues/184
[gh-185]: https://github.com/rmccue/Requests/issues/185
[gh-186]: https://github.com/rmccue/Requests/issues/186
[gh-187]: https://github.com/rmccue/Requests/issues/187
[gh-188]: https://github.com/rmccue/Requests/issues/188
[gh-194]: https://github.com/rmccue/Requests/issues/194
[gh-196]: https://github.com/rmccue/Requests/issues/196
[gh-200]: https://github.com/rmccue/Requests/issues/200
[gh-202]: https://github.com/rmccue/Requests/issues/202
[gh-203]: https://github.com/rmccue/Requests/issues/203
[gh-205]: https://github.com/rmccue/Requests/issues/205
[gh-206]: https://github.com/rmccue/Requests/issues/206
[gh-207]: https://github.com/rmccue/Requests/issues/207
[gh-210]: https://github.com/rmccue/Requests/issues/210
[gh-211]: https://github.com/rmccue/Requests/issues/211
[gh-215]: https://github.com/rmccue/Requests/issues/215
[gh-216]: https://github.com/rmccue/Requests/issues/216
[gh-217]: https://github.com/rmccue/Requests/issues/217
[gh-219]: https://github.com/rmccue/Requests/issues/219
[gh-223]: https://github.com/rmccue/Requests/issues/223
[gh-224]: https://github.com/rmccue/Requests/issues/224
[gh-227]: https://github.com/rmccue/Requests/issues/227
[gh-230]: https://github.com/rmccue/Requests/issues/230
[gh-236]: https://github.com/rmccue/Requests/issues/236
[gh-237]: https://github.com/rmccue/Requests/issues/237
[gh-238]: https://github.com/rmccue/Requests/issues/238
[gh-239]: https://github.com/rmccue/Requests/issues/239
[gh-240]: https://github.com/rmccue/Requests/issues/240

1.6.0
-----
- [Add multiple request support][#23] - Send multiple HTTP requests with both
  fsockopen and cURL, transparently falling back to synchronous when
  not supported.

- [Add proxy support][#70] - HTTP proxies are now natively supported via a
  [high-level API][docs/proxy]. Major props to Ozh for his fantastic work
  on this.

- [Verify host name for SSL requests][#63] - Requests is now the first and only
  standalone HTTP library to fully verify SSL hostnames even with socket
  connections. Thanks to Michael Adams, Dion Hulse, Jon Cave, and Pádraic Brady
  for reviewing the crucial code behind this.

- [Add cookie support][#64] - Adds built-in support for cookies (built entirely
  as a high-level API)

- [Add sessions][#62] - To compliment cookies, [sessions][docs/usage-advanced]
  can be created with a base URL and default options, plus a shared cookie jar.

- Add [PUT][#1], [DELETE][#3], and [PATCH][#2] request support

- [Add Composer support][#6] - You can now install Requests via the
  `rmccue/requests` package on Composer

[docs/proxy]: http://requests.ryanmccue.info/docs/proxy.html
[docs/usage-advanced]: http://requests.ryanmccue.info/docs/usage-advanced.html

[#1]: https://github.com/rmccue/Requests/issues/1
[#2]: https://github.com/rmccue/Requests/issues/2
[#3]: https://github.com/rmccue/Requests/issues/3
[#6]: https://github.com/rmccue/Requests/issues/6
[#9]: https://github.com/rmccue/Requests/issues/9
[#23]: https://github.com/rmccue/Requests/issues/23
[#62]: https://github.com/rmccue/Requests/issues/62
[#63]: https://github.com/rmccue/Requests/issues/63
[#64]: https://github.com/rmccue/Requests/issues/64
[#70]: https://github.com/rmccue/Requests/issues/70

[View all changes][https://github.com/rmccue/Requests/compare/v1.5.0...v1.6.0]

1.5.0
-----
Initial release!