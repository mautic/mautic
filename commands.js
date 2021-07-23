import DynamicContentCommands from './dynamicContent/dynamicContent.commands';

export default (editor) => {
  const dynamicContentCmd = new DynamicContentCommands(editor);

  // Launch Dynamic Content popup: new or edit
  // Once the command is active, it has to be stopped before it can be run again.
  editor.Commands.add('preset-mautic:dynamic-content-open', {
    run: (edtr, sender, options = {}) => {
      // dynamicContentCmd.updateComponentsFromDcStore();
      dynamicContentCmd.showDynamicContentPopup(edtr, sender, options);
    },
    stop: (edtr) => dynamicContentCmd.stopDynamicContentPopup(edtr),
  });
  // Component to {token}
  editor.Commands.add('preset-mautic:dynamic-content-components-to-tokens', {
    run: (edtr) => dynamicContentCmd.convertDynamicContentComponentsToTokens(edtr),
  });
  // Link store to compoennt
  editor.Commands.add('preset-mautic:link-component-to-store-item', {
    run: (edtr, sender, options) =>
      dynamicContentCmd.linkComponentToStoreItem(edtr, sender, options),
  });
  // Fill the component with values.
  editor.Commands.add('preset-mautic:update-dc-components-from-dc-store', {
    run: () => dynamicContentCmd.updateComponentsFromDcStore(),
  });
  // delete store item
  editor.Commands.add('preset-mautic:dynamic-content-delete-store-item', {
    run: (edtr, sender, options) =>
      dynamicContentCmd.deleteDynamicContentStoreItem(edtr, sender, options),
  });
};
