/* eslint-disable no-else-return */
import DynamicContentDomComponents from './dynymicContent/dynamicContent.domcomponents';
import UtilService from './util.service';

// https://grapesjs.com/docs/api/component.html
export default (editor) => {
  const mode = UtilService.getMode(editor);
  if (mode === UtilService.modeEmailHtml) {
    const dcdc = new DynamicContentDomComponents(editor);
    dcdc.addDynamicContentType();
  }
};
