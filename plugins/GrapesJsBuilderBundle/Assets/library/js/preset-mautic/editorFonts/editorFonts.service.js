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

    // first check if we have the 'list' property to work with
    let propertyType = 'list';
    let fontList = fontProperty.get('list');

    // use 'options' if 'list' doesn't exist
    if (typeof fontList === 'undefined') {
      propertyType = 'options';
      fontList = fontProperty.get('options');
    }

    EditorFontsService.updateFontList(editor, fontList, propertyType);
    EditorFontsService.sortFontList(fontList, propertyType);

    fontProperty.set(propertyType, fontList);
    styleManager.render();
  }

  static updateFontList(editor, fontList, propertyType) {
    const canvasHead = editor.Canvas.getDocument().head;

    mauticEditorFonts.forEach((item) => {
      let key = 'name';
      if (propertyType === 'options') key = 'label';

      if (!fontList.find((element) => element[key] === item.name)) {
        if (propertyType === 'list') fontList.push({ value: item.font, name: item.name });
        if (propertyType === 'options') fontList.push({ id: item.font, label: item.name });

        if (item.url && canvasHead) {
          EditorFontsService.appendFontStyleLink(canvasHead, item.url);
        }
      }
    });

    return fontList;
  }

  static sortFontList(fontList, propertyType) {
    if (propertyType === 'list') fontList.sort((a, b) => (a.name < b.name ? -1 : 1));
    if (propertyType === 'options') fontList.sort((a, b) => (a.label < b.label ? -1 : 1));

    return fontList;
  }

  static addFontLinksToHtml(htmlCode) {
    const htmlDoc = new DOMParser().parseFromString(htmlCode, 'text/html');
    const headElem = htmlDoc.head;
    const headLinks = [...headElem.getElementsByTagName('link')];

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
