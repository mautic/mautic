import DynamicContentListeners from './dynamicContent/dynamicContent.listeners';
import PreferenceCenterListeners from './preferenceCenter/preferenceCenter.listeners';

export default (editor, options) => {
  const dynamicContentTabs = [];

  const dcListener = new DynamicContentListeners(editor, dynamicContentTabs);
  dcListener.onLoad();

  const preferenceCenterTabs = [];

  const pcListener = new PreferenceCenterListeners(editor, preferenceCenterTabs);
  pcListener.onLoad();

  // @todo is this needed? why do we copy the original content?
  // this.editor.on('run:mautic-editor-email-mjml-close:before', () => {
  //   mQuery('textarea.builder-html').val(this.getBody());
  // });

  // getBody() {
  //   // =>return the body based on the orginal content as string

  //   // Parse HTML template
  //   const parser = new DOMParser();
  //   const content = parser.parseFromString(this.originalContent, 'text/html');
  //   const body = content.body.innerHTML || mQuery('textarea.builder-mjml').val();

  //   if (!body) {
  //     throw Error('No HTML or MJML content found');
  //   }
  //   return body;
  // }
};
