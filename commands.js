import DynamicContentService from './dynymicContent/dynamicContent.service';
import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  const cmd = editor.Commands;
  const dcService = new DynamicContentService();

  // Launch Code Editor popup
  cmd.add('preset-mautic:code-edit', {
    run: DynamicContentCommands.launchCodeEdit,
    stop: dcService.grapesConvertDynamicContentTokenToSlot,
  });

  // Launch Dynamic Content popup
  cmd.add('preset-mautic:dynamic-content', {
    run: DynamicContentCommands.launchDynamicContent,
    stop: dcService.grapesConvertDynamicContentTokenToSlot,
  });
};
