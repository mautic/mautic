import DynamicContentCommands from './dynamicContent/dynamicContent.commands';
import PreferenceCenterCommands from './preferenceCenter/preferenceCenter.commands';

export default (editor) => {
  const dynamicContentCmd = new DynamicContentCommands(editor);

  // Launch Dynamic Content popup: new or edit
  // Once the command is active, it has to be stopped before it can be run again.
  editor.Commands.add('preset-mautic:dynamic-content-open', {
    run: (edtr, sender, options = {}) => {
      // dynamicContentCmd.convertDynamicContentTokensToSlots();
      dynamicContentCmd.launchDynamicContentPopup(edtr, sender, options);
    },
    stop: (edtr) => dynamicContentCmd.stopDynamicContentPopup(edtr),
  });
  // Slot to {token}
  editor.Commands.add('preset-mautic:dynamic-content-slots-to-tokens', {
    run: (edtr) => dynamicContentCmd.convertDynamicContentSlotsToTokens(edtr),
  });
  // {token} to slot
  editor.Commands.add('preset-mautic:dynamic-content-tokens-to-slots', {
    run: () => dynamicContentCmd.convertDynamicContentTokensToSlots(),
  });
  // delte store item
  editor.Commands.add('preset-mautic:dynamic-content-delete-store-item', {
    run: (edtr, sender, options) =>
      dynamicContentCmd.deleteDynamicContentStoreItem(edtr, sender, options),
  });

  const preferenceCenterCmd = new PreferenceCenterCommands(editor);

  // Launch Dynamic Content popup: new or edit
  // Once the command is active, it has to be stopped before it can be run again.
  editor.Commands.add('preset-mautic:preference-center-open', {
    run: (edtr, sender, options = {}) => {
      // preferenceCenterCmd.convertPreferenceCenterTokensToSlots();
      preferenceCenterCmd.launchPreferenceCenterPopup(edtr, sender, options);
    },
    stop: (edtr) => preferenceCenterCmd.stopPreferenceCenterPopup(edtr),
  });
  // Slot to {token}
  editor.Commands.add('preset-mautic:preference-center-slots-to-tokens', {
    run: (edtr) => preferenceCenterCmd.convertPreferenceCenterSlotsToTokens(edtr),
  });
  // {token} to slot
  editor.Commands.add('preset-mautic:preference-center-tokens-to-slots', {
    run: () => preferenceCenterCmd.convertPreferenceCenterTokensToSlots(),
  });
  // delte store item
  editor.Commands.add('preset-mautic:preference-center-delete-store-item', {
    run: (edtr, sender, options) =>
      preferenceCenterCmd.deletePreferenceCenterStoreItem(edtr, sender, options),
  });
};
