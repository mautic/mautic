@bender-tags: exportpdf, feature, 77
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification, format

Note: You need the Internet connection to run this test.

1. Click `Export to PDF` toolbar button.
1. Examine the area in the red frame below.

  **Expected:** There is a long token string in the frame.

  **Unexpected:** Frame is empty or says 'undefined'.

1. Wait for the file to download and open it.

  **Expected:** No information about being created with CKEditor was added.

  **Unexpected:** There is an additional note about CKEditor at the bottom of page.
