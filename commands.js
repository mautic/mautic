import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  const cmd = editor.Commands;

  // Launch Code Editor popup / do we need this?
  // cmd.add('preset-mautic:code-edit', {
  //   run: DynamicContentCommands.launchCodeEdit,
  //   stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  // });

  // Launch Dynamic Content popup
  cmd.add('preset-mautic:dynamic-content', {
    run: DynamicContentCommands.launchDynamicContent,
    stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  });
};
