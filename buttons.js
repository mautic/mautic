import ButtonClose from './buttons/buttonClose';
import ButtonApply from './buttons/buttonApply';
import ButtonPreview from './buttons/buttonPreview';

export default (editor, opts = {}) => {
  const { $ } = editor;
  const pm = editor.Panels;
  const defaultPanel = opts.defaultPanel || 'open-blocks';

  // Disable Import code button
  if (!opts.showImportButton) {
    const mjmlImportBtn = pm.getButton('options', 'mjml-import');
    const htmlImportBtn = pm.getButton('options', 'gjs-open-import-template');
    const pageImportBtn = pm.getButton('options', 'gjs-open-import-webpage');

    // MJML import
    if (mjmlImportBtn !== null) {
      pm.removeButton('options', 'mjml-import');
    }

    // Newsletter import
    if (htmlImportBtn !== null) {
      pm.removeButton('options', 'gjs-open-import-template');
    }

    // Webpage import
    if (pageImportBtn !== null) {
      pm.removeButton('options', 'gjs-open-import-webpage');
    }
  }

  // disable useless export button since not all of .then(function (response) {
  // template is exportet (in favour of code editor)
  // Causes issue: https://github.com/artf/grapesjs/issues/3444
  // @todo Possible workaround: hide it with css
  // editor.Panels.getButton('options', 'export-template').attributes.className = 'hide_panel_btn'
  // pm.removeButton('options', 'export-template');

  // Move Undo & Redo inside Commands Panel
  const undo = pm.getButton('options', 'undo');
  const redo = pm.getButton('options', 'redo');

  if (undo !== null) {
    pm.removeButton('options', 'undo');
    pm.addButton('commands', [
      {
        id: 'undo',
        className: 'fa fa-undo',
        attributes: { title: 'Undo' },
        command() {
          editor.runCommand('core:undo');
        },
      },
    ]);
  }

  if (redo !== null) {
    pm.removeButton('options', 'redo');
    pm.addButton('commands', [
      {
        id: 'redo',
        className: 'fa fa-repeat',
        attributes: { title: 'Redo' },
        command() {
          editor.runCommand('core:redo');
        },
      },
    ]);
  }

  // Remove preview button (because they are dublicated?)
  const preview = pm.getButton('options', 'preview');
  if (preview !== null) {
    pm.removeButton('options', 'preview');
  }

  // Remove clear button
  const clear = pm.getButton('options', 'canvas-clear');
  if (clear !== null) {
    pm.removeButton('options', 'canvas-clear');
  }

  // Remove toggle images button
  const toggleImages = pm.getButton('options', 'gjs-toggle-images');
  if (toggleImages !== null) {
    pm.removeButton('options', 'gjs-toggle-images');
  }
  // add editor preview button
  const btnPreview = new ButtonPreview(editor);
  btnPreview.addCommand();
  btnPreview.addButton();


  // add editor apply button
  const btnApply = new ButtonApply(editor);
  btnApply.addCommand();
  btnApply.addButton();

  // add editor close button
  const btnClose = new ButtonClose(editor);
  btnClose.addCommand();
  btnClose.addButton();

  // Do stuff on load
  editor.on('load', () => {
    // Hide Layers Manager
    if (!opts.showLayersManager) {
      const openLayersBtn = pm.getButton('views', 'open-layers');

      if (openLayersBtn !== null) {
        openLayersBtn.set('attributes', {
          style: 'display:none;',
        });
      }
    }

    // Activate by default View Components button
    const viewComponents = pm.getButton('options', 'sw-visibility');
    if (viewComponents) {
      viewComponents.set('active', 1);
    }

    // Load and show settings and style manager
    if (!opts.combineSettingsAndSm) {
      const openTmBtn = pm.getButton('views', 'open-tm');
      const openSm = pm.getButton('views', 'open-sm');
      if (openTmBtn) {
        openTmBtn.set('active', 1);
      }
      if (openSm) {
        openSm.set('active', 1);
      }

      pm.removeButton('views', 'open-tm');

      // Add Settings Sector
      const traitsSector = $(
        '<div class="gjs-sm-sector no-select">' +
        '<div class="gjs-sm-title"><span class="icon-settings fa fa-cog"></span> Settings</div>' +
        '<div class="gjs-sm-properties" style="display: none;"></div></div>'
      );
      const traitsProps = traitsSector.find('.gjs-sm-properties');

      traitsProps.append($('.gjs-trt-traits'));
      $('.gjs-sm-sectors').before(traitsSector);
      traitsSector.find('.gjs-sm-title').on('click', () => {
        const traitStyle = traitsProps.get(0).style;
        const hidden = traitStyle.display === 'none';

        if (hidden) {
          traitStyle.display = 'block';
        } else {
          traitStyle.display = 'none';
        }
      });

      // Open settings
      traitsProps.get(0).style.display = 'block';
    }

    // Open the default panel
    const openBlocksBtn = editor.Panels.getButton('views', defaultPanel);
    if (openBlocksBtn) {
      openBlocksBtn.set('active', 1);
    }
  });
  // Workaround to set the default panel when the editor is reopened. Missing a dedicated event for 'opening'.
  editor.on('update', () => {
    const btn = editor.Panels.getButton('views', defaultPanel);
    btn.set('active', 1);
  });
};
