@bender-tags: exportpdf, feature, 4
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, notification

1. Read the expected results first as there will be a sequence of things happening quickly to examine.
1. Click `Export to PDF` button in the first editor.

  **Expected:**

  * Button is disabled after click.
  * Nofitication bar of `info` type appeared.
  * When download started, notification type changed to `success`.
  * Once download finished, button is enabled again.
  * After 3 seconds notification bar disappeared.

  **Unexpected:**

  * Button wasn't disabled.
  * Button wasn't enabled after download started.
  * Notifications were incorrect.
  * Notification bar didn't disappear.

1. Click `Export to PDF` button in the second editor.

  **Expected:**

  * Neither notifications nor alerts appeared.
  * File was downloaded.
  * Button was disabled for the time between click and download.

  **Unexpected:**

  * Notification or browser alert appeared.
  * File wasn't downloaded.
  * Button wasn't disabled for the time between click and download.
