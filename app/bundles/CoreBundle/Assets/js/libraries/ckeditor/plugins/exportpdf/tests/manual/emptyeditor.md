@bender-tags: exportpdf, feature, 11
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification

**Note:** At the beginning open the console.

1. Click `Export to PDF` button (the one next to the `Source` button) in the first editor.
1. Wait for the file to download.
1. Open the file.

  **Expected:**

  * Empty file was downloaded.
  * No errors in console.

  **Unexpected:**

  * File wasn't downloaded.
  * File was downloaded but can't be opened.
  * Error in the console appeared.

1. Click `Export to PDF` button in the second editor.

  **Expected:**

  * File wasn't downloaded.
  * The notification with error appeared in the editor.
  * There is an error message in the console.

  **Unexpected:**

  * File was downloaded and can't be opened.
  * Success notification was displayed.
