import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  const dynamicContentCmd = new DynamicContentCommands();

  // Launch Dynamic Content popup: new or edit
  // Once the command is active, it has to be stopped before it can be run again.
  editor.Commands.add('preset-mautic:dynamic-content-open', {
    run: (edtr, sender, options = {}) =>
      dynamicContentCmd.launchDynamicContent(edtr, sender, options),
    stop: (edtr, sender, options = {}) =>
      dynamicContentCmd.grapesConvertDynamicContentTokenToSlot(edtr, sender, options),
  });

  // Open popup to edit the canvas source code
  editor.Commands.add('preset-mautic:code-edit', {
    run: DynamicContentCommands.launchCodeEdit,
    stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  });
};
