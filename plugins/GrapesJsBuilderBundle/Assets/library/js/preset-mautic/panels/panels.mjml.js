export default class PanelsMjml {
  panelManager;

  editor;

  constructor(editor) {
    this.editor = editor;
    this.panelManager = editor.Panels;
  }

  restylePanels() {
    const iconStyle = 'style="display: block; max-width: 22px"';

    if (typeof this.panelManager.getButton('views', 'open-blocks') !== 'undefined') {
      this.panelManager.getButton('views', 'open-blocks').set({
        className: '', // resets the custom classes, so only displays the default ones and we don't render the icon font anymore
        label: `<svg ${iconStyle} viewBox="0 0 24 24">
          <path fill="currentColor" d="M17,13H13V17H11V13H7V11H11V7H13V11H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z" />
        </svg>`,
      });
    }

    if (typeof this.panelManager.getButton('views', 'open-sm') !== 'undefined') {
      this.panelManager.getButton('views', 'open-sm').set({
        className: '', // resets the custom classes, so only displays the default ones and we don't render the icon font anymore
        label: `<svg style="display: block; max-width: 22px" viewBox="0 0 24 24">
          <path fill="currentColor" d="M20.71,4.63L19.37,3.29C19,2.9 18.35,2.9 17.96,3.29L9,12.25L11.75,15L20.71,6.04C21.1,5.65 21.1,5 20.71,4.63M7,14A3,3 0 0,0 4,17C4,18.31 2.84,19 2,19C2.92,20.22 4.5,21 6,21A4,4 0 0,0 10,17A3,3 0 0,0 7,14Z" />
        </svg>`,
      });
    }

    if (typeof this.panelManager.getButton('options', 'fullscreen') !== 'undefined') {
      this.panelManager.getButton('options', 'fullscreen').set({
        className: '', // resets the custom classes, so only displays the default ones and we don't render the icon font anymore
        label: `<svg ${iconStyle} viewBox="0 0 24 24">
          <path fill="currentColor" d="M5,5H10V7H7V10H5V5M14,5H19V10H17V7H14V5M17,14H19V19H14V17H17V14M10,17V19H5V14H7V17H10Z" />
        </svg>`,
      });
    }

    if (typeof this.panelManager.getButton('options', 'sw-visibility') !== 'undefined') {
      this.panelManager.getButton('options', 'sw-visibility').set({
        className: '', // resets the custom classes, so only displays the default ones and we don't render the icon font anymore
        label: `<svg ${iconStyle} viewBox="0 0 24 24">
          <path fill="currentColor" d="M15,5H17V3H15M15,21H17V19H15M11,5H13V3H11M19,5H21V3H19M19,9H21V7H19M19,21H21V19H19M19,13H21V11H19M19,17H21V15H19M3,5H5V3H3M3,9H5V7H3M3,13H5V11H3M3,17H5V15H3M3,21H5V19H3M11,21H13V19H11M7,21H9V19H7M7,5H9V3H7V5Z" />
        </svg>`,
      });
    }
  }
}
