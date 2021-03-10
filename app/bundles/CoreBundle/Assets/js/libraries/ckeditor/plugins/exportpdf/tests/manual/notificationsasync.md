@bender-tags: exportpdf, feature, 4
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification

1. Click `Export to PDF` button (the one next to the `Source` button) in the first editor.
1. Watch appearing notifications.

  **Expected:**

  * Notification `Processing PDF document...` was visible for about 2 seconds.
  * Progress steps were: `0`, `0.5`, `success`.

  **Unexpected:**

  Notification disappeared too fast to be noticable.

1. Do the same in the second editor.

  **Expected:**

  * Notification `Processing PDF document...` was visible for about 2 seconds.
  * Progress steps were: `0.2`, `0.5`, `success`.

  **Unexpected:**

  Notification disappeared too fast to be noticable.
