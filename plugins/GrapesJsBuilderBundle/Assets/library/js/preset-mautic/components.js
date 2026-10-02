/* eslint-disable no-else-return */
import DynamicContentDomComponents from './dynamicContent/dynamicContent.domcomponents';

// https://grapesjs.com/docs/api/component.html
export default (editor) => {
  DynamicContentDomComponents.addDynamicContentType(editor);
};
