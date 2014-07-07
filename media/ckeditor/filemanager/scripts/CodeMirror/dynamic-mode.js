var instantiateCodeMirror = function(CmMode, config) {

	// if no code highlight needed, we apply default settings
	if (!config.edit.codeHighlight) {

		currentmode = 'default';

	// we highlight code according to extension file
	} else {

		if (CmMode === 'txt') {
			var currentmode = 'default';
		}
		if (CmMode === 'js') {
			loadJS('./scripts/CodeMirror/mode/javascript/javascript.js');
			var currentmode = 'javascript';
		}
		if (CmMode === 'css') {
			loadJS('./scripts/CodeMirror/mode/css/css.js');
			var currentmode = 'css';
		}
		if (CmMode === 'html') {
			loadJS('./scripts/CodeMirror/mode/xml/xml.js');
			var currentmode = 'text/html';
		}
		if (CmMode === 'xml') {
			loadJS('./scripts/CodeMirror/mode/xml/xml.js');
			var currentmode = 'application/xml';
		}
		if (CmMode === 'php') {
			loadJS('./scripts/CodeMirror/mode/htmlmixed/htmlmixed.js');
			loadJS('./scripts/CodeMirror/mode/xml/xml.js');
			loadJS('./scripts/CodeMirror/mode/javascript/javascript.js');
			loadJS('./scripts/CodeMirror/mode/css/css.js');
			loadJS('./scripts/CodeMirror/mode/clike/clike.js');
			loadJS('./scripts/CodeMirror/mode/php/php.js');
			var currentmode = 'application/x-httpd-php';
		}
		if (CmMode === 'sql') {
			loadJS('./scripts/CodeMirror/mode/sql/sql.js');
			var currentmode = 'text/x-mysql';
		}

	}

	var editor = CodeMirror.fromTextArea(document.getElementById("edit-content"), {
		styleActiveLine : true,
		viewportMargin : Infinity,
		lineNumbers : config.edit.lineNumbers,
		lineWrapping : config.edit.lineWrapping,
		theme : config.edit.theme
	});

	// we finnaly set option
	editor.setOption("mode", currentmode);
	//console.log('CodeMirror mode  : ' + editor.getOption("mode"));

	return editor;
}
