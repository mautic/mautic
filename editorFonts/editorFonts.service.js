import ContentService from '../content.service';

export default class EditorFontsService {
  static loadEditorFonts(editor) {
    const styleManager = editor.StyleManager;

    if (!styleManager) {
      // eslint-disable-next-line no-console
      console.error('No GrapesJS Style Manager found.');
      return;
    }

    const fontProperty = styleManager.getProperty('typography', 'font-family');
    if (!fontProperty) {
      // eslint-disable-next-line no-console
      console.error('No font properties found in the typography sector.');
      return;
    }

    const fontList = fontProperty.get('list');
    EditorFontsService.updateFontList(editor, fontList);
    EditorFontsService.sortFontList(fontList);

    fontProperty.set('list', fontList);
    styleManager.render();
  }

  static updateFontList(editor, fontList) {
    const canvasHead = editor.Canvas.getDocument().head;

    // eslint-disable-next-line no-undef
    mauticEditorFonts.forEach((item) => {
      if (!fontList.find((element) => element.name === item.name)) {
        fontList.push({ value: item.font, name: item.name });

        if (item.url && canvasHead) {
          EditorFontsService.appendFontStyleLink(canvasHead, item.url);
        }
      }
    });

    return fontList;
  }

  static sortFontList(fontList) {
    fontList.sort((a, b) => (a.name < b.name ? -1 : 1));

    return fontList;
  }

  static addFontLinksToHtml(htmlCode) {
    const htmlDoc = new DOMParser().parseFromString(htmlCode, 'text/html');
    const headElem = htmlDoc.head;
    const headLinks = [...headElem.getElementsByTagName('link')];

    // eslint-disable-next-line no-undef
    mauticEditorFonts.forEach((item) => {
      // Check which fonts are used
      if (item.url && htmlCode.indexOf(item.font) !== -1) {
        // Link fonts style, if they are not already linked
        if (!headLinks.find((link) => link.href === item.url)) {
          EditorFontsService.appendFontStyleLink(headElem, item.url);
        }
      }
    });

    return ContentService.serializeHtmlDocument(htmlDoc);
  }

  static appendFontStyleLink(head, url) {
    const linkElem = EditorFontsService.createStylesheetLink(url);
    return head.appendChild(linkElem);
  }

  static createStylesheetLink(url) {
    const linkElem = document.createElement('link');
    linkElem.rel = 'stylesheet';
    linkElem.type = 'text/css';
    linkElem.href = url;

    return linkElem;
  }
}
