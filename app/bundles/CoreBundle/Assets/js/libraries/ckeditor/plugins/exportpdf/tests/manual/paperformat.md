@bender-tags: exportpdf, bug, 24
@bender-ui: collapsed
@bender-include: ../_helpers/tools.js
@bender-ckeditor-plugins: wysiwygarea, toolbar, basicstyles, notification

1. Click `Export to PDF` button (the one next to the `Source` button) in the first editor.
1. Wait for the file to download.
1. Do the same in the second editor.
1. Compare paper format in files.

  **Expected:**

  * First editor produced file in `A4` format (`8.27in x 11.7in`);
  * Second editor produced file in `A6` format (`4.13in x 5.83in`).

  **Unexpected:**

  Any file is in different format than intended (e.g. `Letter` - `8.5in x 11in`).
