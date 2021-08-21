/* eslint-disable no-param-reassign */
/* eslint-disable no-unused-expressions */
import 'grapesjs/dist/css/grapes.min.css';
import grapesjs from 'grapesjs';

const editor = grapesjs.init({
  fromElement: 1,
  container: '#gjs',
  height: '100%',
  avoidInlineStyle: false,
  plugins: [],
});

editor.setCustomRte({
  /**
   * Enabling the custom RTE
   * @param  {HTMLElement} el This is the HTML node which was selected to be edited
   * @param  {Object} rte It's the instance you'd return from the first call of enable().
   *                      At the first call it'd be undefined. This is useful when you need
   *                      to check if the RTE is already enabled on the component
   * @return {Object} The return should be the RTE initialized instance
   */
  enable(el, rte) {
    // If already exists just focus
    if (rte) {
      this.focus(el, rte); // implemented later
      return rte;
    }

    // CKEditor initialization
    // eslint-disable-next-line no-undef
    rte = CKEDITOR.inline(el, {
      // Your configurations...
      sharedSpaces: {
        top: editor.RichTextEditor.getToolbarEl(),
      },
    });

    this.focus(el, rte);
    return rte;
  },
  focus(el, rte) {
    // Do nothing if already focused
    if (rte && rte.focusManager.hasFocus) {
      return;
    }
    el.contentEditable = true;
    rte && rte.focus();
  },
  disable(el, rte) {
    el.contentEditable = false;
    if (rte && rte.focusManager) {
      rte.focusManager.blur(true);
    }
  },
});

editor.on('rteToolbarPosUpdate', (pos) => {
  if (pos.top <= pos.canvasTop) {
    pos.top = pos.elementTop + pos.elementHeight;
  }
});

// add custom fonts options on editor's font list
// editor.on('load', () => {
//   let styleManager = editor.StyleManager;
//   let fontProperty = styleManager.getProperty('typography', 'font-family');

//   let list = [];
//   // empty list
//   fontProperty.set('list', list);

//   // custom list
//   list.push(fontProperty.addOption({ value: 'Montserrat, sans-serif', name: 'Montserrat' }));
//   list.push(fontProperty.addOption({ value: 'Open Sans, sans-serif', name: 'Open Sans' }));
//   fontProperty.set('list', list);

//   styleManager.render();
// });
