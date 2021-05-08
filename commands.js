import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  const dynamicContentCmd = new DynamicContentCommands();

  // Launch Dynamic Content popup: new or edit
  // Once the command is active, it has to be stopped before it can be run again.
  editor.Commands.add('preset-mautic:dynamic-content-open', {
    run: (edtr, sender, options = {}) =>
      dynamicContentCmd.launchDynamicContentPopup(edtr, sender, options),
    stop: (edtr) => edtr.runCommand('preset-mautic:dynamic-content-tokens-to-slots'),
  });
  editor.Commands.add('preset-mautic:dynamic-content-slots-to-tokens', {
    run: (edtr) => dynamicContentCmd.convertDynamicContentSlotsToTokens(edtr),
  });
  editor.Commands.add('preset-mautic:dynamic-content-tokens-to-slots', {
    run: (edtr) => dynamicContentCmd.convertDynamicContentTokenToSlot(edtr),
  });

  // Open popup to edit the canvas source code
  editor.Commands.add('preset-mautic:code-edit', {
    run: DynamicContentCommands.launchCodeEdit,
    stop: editor.runCommand('preset-mautic:dynamic-content-tokens-to-slots'),
  });
};
