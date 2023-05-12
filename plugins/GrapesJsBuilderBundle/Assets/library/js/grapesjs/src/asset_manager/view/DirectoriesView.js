import Backbone from "backbone";

export default Backbone.View.extend({
  assetManagerProps: null,
  module: null,
  pfx: null,
  ppfx: null,

  events: {
    "change #assets-directories-select": "directorySelected",
    "click #assets-new-directory": "newDirectory",
  },

  template() {
    const options = this.collection.map((item) => {
      let selected = "";

      if (this.assetManagerProps.get("activeDirectory") === item.getPath()) {
        selected = "selected";
      }

      return `<option value="${item.getPath()}" ${selected}>${item.getPath()}</option>`;
    });

    let newDirectoryHtml = '';

    if (this.assetManagerProps.get('canManageFolders')) {
      newDirectoryHtml = `<button id="assets-new-directory" class="${this.ppfx}btn-prim">New Directory</button>`;
    }

    return `
      <div class="${this.pfx}assets-directories gjs-am-add-asset" data-el="directories" style="margin-bottom: 10px; padding: 0 5px;">
        <div class="${this.ppfx}field ${this.pfx}add-field">
          <select id="assets-directories-select">${options}</select>
        </div>
        ${newDirectoryHtml}
        <div style="clear:both"></div>
      </div>
    `;
  },

  initialize({ collection, config, assetManagerProps, module }) {
    this.collection = collection;
    this.pfx = config.stylePrefix || "";
    this.ppfx = config.pStylePrefix || "";
    this.module = module;
    this.assetManagerProps = assetManagerProps;

    this.listenTo(this.collection, "add", this.render);
    this.listenTo(this.assetManagerProps, "change", this.render);
  },

  directorySelected(event) {
    if (event.target.value === this.assetManagerProps.get("activeDirectory")) {
      return;
    }

    this.assetManagerProps.set({
      activeDirectory: event.target.value,
    });
  },

  updateFiles() {
    const selectedDirectory = this.assetManagerProps.get("activeDirectory");

    // set timeout to avoid double loading assets on the same assetManagerProps update
    setTimeout(() => {
      this.assetManagerProps.set({
        isLoading: true,
      });
    }, 0);

    fetch(
      `${this.assetManagerProps.get(
        "assetsPath"
      )}?directory=${selectedDirectory}`,
      {
        headers: {
          "Content-Type": "application/json",
        },
      }
    )
      .then((response) => response.json())
      .then(({ assets, directories }) => {
        this.module.load({ assets });
        this.assetManagerProps.set({
          isLoading: false,
        });
      });
  },

  newDirectory() {
    const newDirectory = prompt("Please enter your new folder name");

    if (!newDirectory) {
      return;
    }

    this.assetManagerProps.set({
      isLoading: true,
    });

    fetch(this.assetManagerProps.get("newDirectoryPath"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        newDirectory,
        activeDirectory: this.assetManagerProps.get("activeDirectory"),
      }),
    })
      .then((response) => response.json())
      .then((response) => {
        if (response.status === "error") {
          alert(`There was an error: ${response.message}`);
          return;
        }

        const { assets, directories } = response;

        this.collection.reset(directories);
        this.module.load({ assets });
        this.assetManagerProps.set({
          isLoading: false,
        });
      });
  },

  render(change) {
    if (typeof change !== "undefined" && change.changed.activeDirectory) {
      this.updateFiles();
    }
    this.$el.empty();
    this.$el.append(this.template());
    this.el.className = `${this.ppfx}asset-directories`;
    return this;
  },
});
