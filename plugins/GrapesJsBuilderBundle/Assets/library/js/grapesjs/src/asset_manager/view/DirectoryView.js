import { isFunction } from "underscore";
import AssetView from "./AssetView";
import html from "utils/html";

export default AssetView.extend({
  events: {
    "click [data-toggle=asset-remove]": "onRemove",
    click: "onClick",
    dblclick: "onDblClick",
  },

  getPreview() {
    const { pfx, ppfx, model } = this;
    const icon = model.get("icon");
    return html`
      <div class="${pfx}dir-icon">
        <span class="${icon}" aria-hidden="true"></span>
      </div>
    `;
  },

  getInfo() {
    const { pfx, model } = this;
    let name = model.get("path");
    return html`
      <div class="${pfx}name">${name}</div>
      <div class="${pfx}dimensions">Directory</div>
    `;
  },

  init(o) {
    const pfx = this.pfx;
    this.className += ` ${pfx}asset-image`;
  },

  /**
   * Triggered when the asset is clicked
   * @private
   * */
  onClick() {
    const { model, pfx } = this;

    this.module
      .getAssetManagerProps()
      .set({ activeDirectory: model.get("absolutePath") });

    // implement dir selection here
  },

  /**
   * Triggered when the asset is double clicked
   * @private
   * */
  onDblClick() {
    this.onClick();
  },

  /**
   * Remove asset from collection
   * @private
   * */
  onRemove(e) {
    // implement folder removal?
    // e.stopImmediatePropagation();
    // this.model.collection.remove(this.model);
  },
});
