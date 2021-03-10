/*
 Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
*/
(function(){window.exportPdfUtils={useXHR:function(b,a){var c=sinon.useFakeXMLHttpRequest(),d;c.onCreate=function(a){d=a};b.execCommand("exportPdf");a&&a(d);c.restore()},getDefaultConfig:function(b,a){return CKEDITOR.tools.object.merge({extraPlugins:"exportpdf",exportPdf_appId:"cke4-tests-"+b},a)},initManualTest:function(){bender.loadExternalPlugin("exportpdf","/apps/plugin/");bender.tools.ignoreUnsupportedEnvironment("exportpdf")},toAbsoluteUrl:function(b,a){return(a?a:window.location.origin)+b}}})();