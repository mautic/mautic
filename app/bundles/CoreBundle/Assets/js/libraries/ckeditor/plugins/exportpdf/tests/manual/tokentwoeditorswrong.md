@bender-tags: exportpdf, feature, 77
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification, format

Note: You need the Internet connection to run this test.

1. Click `Export to PDF` button in both editors.

1. Examine the area in the red frames below each editor.

  **Expected:** First box contains token value and the second one `undefined`.

  **Unexpected:** Values in both boxes are the same or none of them is `undefined`.
