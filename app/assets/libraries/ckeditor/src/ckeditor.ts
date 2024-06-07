/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

// The editor creator to use.
import { ClassicEditor as ClassicEditorBase } from '@ckeditor/ckeditor5-editor-classic';
import { Essentials } from '@ckeditor/ckeditor5-essentials';
import { CKFinderUploadAdapter } from '@ckeditor/ckeditor5-adapter-ckfinder';
import { Autoformat } from '@ckeditor/ckeditor5-autoformat';
import { Bold, Italic, Underline, Code, Strikethrough, Subscript, Superscript } from '@ckeditor/ckeditor5-basic-styles';
import { BlockQuote } from '@ckeditor/ckeditor5-block-quote';
import { CKBox } from '@ckeditor/ckeditor5-ckbox';
import { CKFinder } from '@ckeditor/ckeditor5-ckfinder';
import { EasyImage } from '@ckeditor/ckeditor5-easy-image';
import { Heading } from '@ckeditor/ckeditor5-heading';
import { Image, ImageCaption, ImageInsert, ImageResize, ImageStyle, ImageToolbar, ImageUpload, PictureEditing, AutoImage, ImageBlock, ImageInline } from '@ckeditor/ckeditor5-image';
import { FontBackgroundColor, FontColor, FontFamily, FontSize } from "@ckeditor/ckeditor5-font";
import { Indent, IndentBlock } from '@ckeditor/ckeditor5-indent';
import { Link, AutoLink, LinkImage } from '@ckeditor/ckeditor5-link';
import { Autosave } from "@ckeditor/ckeditor5-autosave";
import { List } from '@ckeditor/ckeditor5-list';
import { MediaEmbed } from '@ckeditor/ckeditor5-media-embed';
import { Paragraph } from '@ckeditor/ckeditor5-paragraph';
import { PasteFromOffice } from '@ckeditor/ckeditor5-paste-from-office';
import { Table, TableCellProperties, TableProperties, TableToolbar } from '@ckeditor/ckeditor5-table';
import { TextTransformation } from '@ckeditor/ckeditor5-typing';
import { CloudServices } from '@ckeditor/ckeditor5-cloud-services';
import { Alignment } from "@ckeditor/ckeditor5-alignment";
import { RemoveFormat } from "@ckeditor/ckeditor5-remove-format";
import { SourceEditing } from "@ckeditor/ckeditor5-source-editing";
import { GeneralHtmlSupport } from "@ckeditor/ckeditor5-html-support";
import { Mention } from "@ckeditor/ckeditor5-mention";
import TokenPlugin from './TokenPlugin';

export default class ClassicEditor extends ClassicEditorBase {
    public static override builtinPlugins = [
        ImageInsert,
        IndentBlock,
        ImageInline,
        Superscript,
        ImageBlock,
        TableProperties,
        TableCellProperties,
        Subscript,
        Strikethrough,
        Mention,
        LinkImage,
        ImageResize,
        Alignment,
        Code,
        AutoImage,
        AutoLink,
        Autosave,
        FontBackgroundColor,
        RemoveFormat,
        SourceEditing,
        GeneralHtmlSupport,
        TokenPlugin,
        FontColor,
        FontFamily,
        FontSize,
        Essentials,
        Underline,
        CKFinderUploadAdapter,
        Autoformat,
        Bold,
        Italic,
        BlockQuote,
        CKBox,
        CKFinder,
        CloudServices,
        EasyImage,
        Heading,
        Image,
        ImageCaption,
        ImageStyle,
        ImageToolbar,
        ImageUpload,
        Indent,
        Link,
        List,
        MediaEmbed,
        Paragraph,
        PasteFromOffice,
        PictureEditing,
        Table,
        TableToolbar,
        TextTransformation
    ];
}
