# CKEditor 4 Export to PDF Plugin Changelog

## ckeditor4-plugin-exportpdf 1.0.2

Other Changes:

*   Updated year in license headers.

## ckeditor4-plugin-exportpdf 1.0.1

Other Changes:

*   Improved external CSS support for [Classic Editor](https://ckeditor.com/docs/ckeditor4/latest/examples/classic.html) by handling exceptions and displaying convenient [error messages](https://ckeditor.com/docs/ckeditor4/latest/guide/dev_errors.html#exportpdf-stylesheets-incaccessible).

## ckeditor4-plugin-exportpdf 1.0.0

The first stable release of the CKEditor 4 WYSIWYG Editor Export to PDF plugin. After a few months of the beta phase, testing and listening to community feedback, the CKEditor 4 Export to PDF plugin is stable and can be used with full confidence. Enjoy!

New Features:

*   Introduced access control mechanism. The plugin now can be configured with additional [exportPdf_tokenUrl](https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-exportPdf_tokenUrl) option to allow commercial use.

## ckeditor4-plugin-exportpdf 0.1.2

Other Changes:

*   Improved [plugin API documentation](https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-exportPdf_fileName).
*   Updated Export to PDF plugin npm readme to link to the [official plugin documentation](https://ckeditor.com/docs/ckeditor4/latest/features/exporttopdf.html).

## ckeditor4-plugin-exportpdf 0.1.1

Other Changes:

*   Renamed the Export to PDF plugin button from `exportPdf` to `ExportPdf`. The `ExportPdf` name should now be used while setting up a custom toolbar with the [CKEDITOR.config.toolbar](https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbar) configuration option.

## ckeditor4-plugin-exportpdf 0.1.0

The first beta release of the CKEditor 4 Export to PDF plugin.

This plugin allows you to easily print your WYSIWYG editor content to a PDF file. When enabled, this feature sends the content of your editor together with the styles that are used to display it to the CKEditor Cloud Services HTML to PDF converter service. The service then generates a PDF document that can be downloaded by the user.

Available features:

*   Exporting HTML content from CKEditor 4 WYSIWYG editor to PDF with a single click.
*   Setting a custom name for the generated PDF file.
*   Handling relative image paths.
*   Changing the appearance of the PDF document (like margins, text styling, custom headers and footers etc.) with custom CSS styles.
*   Pre-processing HTML content via synchronous and asynchronous code before the generation of the PDF file.
