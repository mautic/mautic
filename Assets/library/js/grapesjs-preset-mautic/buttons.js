export default (editor, opts = {}) => {
  const $ = editor.$;
  const pm = editor.Panels;

  // Add function within builder to edit source code
  if (opts.sourceEdit) {
    pm.addButton('options', [
      {
        id: 'code-edit',
        className: 'fa fa-edit',
        command: 'preset-mautic:code-edit',
        attributes: {
          title: opts.sourceEditModalTitle,
        },
      },
    ]);
  }

  // Disable Import code button
  if (!opts.showImportButton) {
    let mjmlImportBtn = pm.getButton('options', 'mjml-import');
    let htmlImportBtn = pm.getButton('options', 'gjs-open-import-template');
    let pageImportBtn = pm.getButton('options', 'gjs-open-import-webpage');

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

  // Move Undo & Redo inside Commands Panel
  let undo = pm.getButton('options', 'undo');
  let redo = pm.getButton('options', 'redo');

  if (undo !== null) {
    pm.removeButton('options', 'undo');
    pm.addButton('commands', [
      {
        id: 'undo',
        className: 'fa fa-undo',
        attributes: { title: 'Undo' },
        command: function () {
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
        command: function () {
          editor.runCommand('core:redo');
        },
      },
    ]);
  }

  // Remove preview button
  let preview = pm.getButton('options', 'preview');

  if (preview !== null) {
    pm.removeButton('options', 'preview');
  }

  // Remove clear button
  let clear = pm.getButton('options', 'canvas-clear');

  if (clear !== null) {
    pm.removeButton('options', 'canvas-clear');
  }

  // Remove toggle images button
  let toggleImages = pm.getButton('options', 'gjs-toggle-images');

  if (toggleImages !== null) {
    pm.removeButton('options', 'gjs-toggle-images');
  }

  // Do stuff on load
  editor.on('load', function () {
    // Hide Layers Manager
    if (!opts.showLayersManager) {
      let openLayersBtn = pm.getButton('views', 'open-layers');

      if (openLayersBtn !== null) {
        openLayersBtn.set('attributes', {
          style: 'display:none;',
        });
      }
    }

    // Activate by default View Components button
    let viewComponents = pm.getButton('options', 'sw-visibility');
    viewComponents && viewComponents.set('active', 1);

    // Load and show settings and style manager
    let openTmBtn = pm.getButton('views', 'open-tm');
    openTmBtn && openTmBtn.set('active', 1);
    let openSm = pm.getButton('views', 'open-sm');
    openSm && openSm.set('active', 1);

    pm.removeButton('views', 'open-tm');

    // Add Settings Sector
    let traitsSector = $(
      '<div class="gjs-sm-sector no-select">' +
        '<div class="gjs-sm-title"><span class="icon-settings fa fa-cog"></span> Settings</div>' +
        '<div class="gjs-sm-properties" style="display: none;"></div></div>'
    );
    let traitsProps = traitsSector.find('.gjs-sm-properties');

    traitsProps.append($('.gjs-trt-traits'));
    $('.gjs-sm-sectors').before(traitsSector);
    traitsSector.find('.gjs-sm-title').on('click', function () {
      let traitStyle = traitsProps.get(0).style;
      let hidden = traitStyle.display === 'none';

      if (hidden) {
        traitStyle.display = 'block';
      } else {
        traitStyle.display = 'none';
      }
    });

    // Open settings
    traitsProps.get(0).style.display = 'block';

    // Open block manager
    let openBlocksBtn = editor.Panels.getButton('views', 'open-blocks');
    openBlocksBtn && openBlocksBtn.set('active', 1);
  });
};
