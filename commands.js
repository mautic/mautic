import DynamicContentService from './dynymicContent/dynamicContent.service';
import DynamicContentCommands from './dynymicContent/dynamicContent.command';

export default (editor) => {
  const cmd = editor.Commands;

  // Launch Code Editor popup
  cmd.add('preset-mautic:code-edit', {
    run: DynamicContentCommands.launchCodeEdit,
    stop: DynamicContentService.grapesConvertDynamicContentTokenToSlot,
  });

  // Launch Dynamic Content popup
  cmd.add('preset-mautic:dynamic-content', {
    run: DynamicContentCommands.launchDynamicContent,
    stop: DynamicContentService.grapesConvertDynamicContentTokenToSlot,
  });
};
