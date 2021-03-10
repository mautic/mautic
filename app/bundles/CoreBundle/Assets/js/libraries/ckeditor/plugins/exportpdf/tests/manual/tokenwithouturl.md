@bender-tags: exportpdf, feature, 77
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification, format

Note: You need the Internet connection to run this test.

1. Open and examine console.

  **Expected:** `exportpdf-no-token-url` warning appeared.

  **Unexpected:** No warning.

1. Click `Export to PDF` button in the editor.
1. Examine the area in the red frame below.

  **Expected:** Frame has text `undefined`.

  **Unexpected:** There is a long token string in the frame.

1. Examine console.

  **Expected:** `exportpdf-no-token` warning appeared.

  **Unexpected:** No warning.

1. Wait for the file to download and open it.

  **Expected:** File contains info about being created with CKEditor.

  **Unexpected:** No copyright info was added.
