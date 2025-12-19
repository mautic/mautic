# MauticAnalyticsBundle

Analytics bundle for Mautic providing accurate dwell time tracking and automatic asset tracking.

## Features

### 1. Dwell Time Tracking

This plugin adds reliable dwell time tracking using the `navigator.sendBeacon()` API to record when visitors leave the page.

#### Problem Solved

Previously, `date_left` was only recorded when a visitor made a subsequent page hit. This caused:
- **Bounces had no dwell time data** - single-page visits never recorded `date_left`
- **Inaccurate bounce rate calculations** - bounce detection relied on `date_left` being NULL
- **Skewed A/B test criteria** - dwell time comparisons were unreliable

#### Solution

Added a lightweight beacon endpoint (`/mtc/leave`) that the JavaScript tracking code calls when visitors leave the page:

1. **New route**: `/mtc/leave`
2. **JavaScript beacon**: Uses `navigator.sendBeacon()` for reliable delivery during page unload
3. **Event coverage**: Listens to both `pagehide` (navigation) and `visibilitychange` (tab close/minimize) events

### 2. Auto Asset Tracking

Automatically tracks clicks on downloadable files by creating Mautic Assets on-the-fly.

**Enable in:** Settings → Plugins → Mautic Analytics → Features → "Auto-track file downloads as Assets"

#### How It Works

1. **JavaScript Detection**: Listens for clicks on links with trackable file extensions
2. **Server-side Validation**: Checks if URL is already a local or remote Mautic Asset (database lookup)
3. **Auto Asset Creation**: If new, creates a remote Asset with title from filename
4. **Tracking Redirect**: Redirects through Mautic's `/asset/{id}:{alias}` for proper tracking

#### Supported File Types

Uses Mautic's configured `allowed_extensions` from Configuration → Asset Settings.

Default: csv, doc, docx, epub, gif, jpg, jpeg, mpg, mpeg, mp3, odt, odp, ods, pdf, png, ppt, pptx, tif, tiff, txt, xls, xlsx, wav

#### Technical Details

- Route: `/mtc/download/track`
- Assets created as "remote" storage type with original URL as `remotePath`
- Title auto-generated from filename by `Asset::setFileNameFromRemote()`
- Existing assets reused (no duplicates)
- Database lookup ensures no false positives

## Installation

1. Copy the plugin to `plugins/MauticAnalyticsBundle`
2. Clear Mautic cache: `bin/console cache:clear`
3. Install/update plugins: `bin/console mautic:plugins:reload`
4. Enable the plugin in Settings → Plugins → Mautic Analytics

## Based On

Dwell time tracking is based on [PR #7404](https://github.com/Webmecanik/Automation_dev/pull/7404).
