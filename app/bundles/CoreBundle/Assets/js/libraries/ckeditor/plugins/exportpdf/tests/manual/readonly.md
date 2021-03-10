@bender-tags: exportpdf, feature, 1
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification

1. Examine `Export to PDF` button (the one next to the `Source` button).

  **Expected:**

  Button is clickable.

  **Unexpected:**

  Button is inactive.

1. Click the button.
1. Wait for the file to download.

  **Expected:**

  File with correct content was downloaded.

  **Unexpected:**

  File was not downloaded or its content is incorrect.

1. Click `Toggle read-only mode` button.
1. Repeat steps 1-3.
