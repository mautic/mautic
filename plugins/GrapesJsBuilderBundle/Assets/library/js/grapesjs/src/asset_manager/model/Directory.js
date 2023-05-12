import Backbone from "backbone";

/**
 * @property {String} path Directory path
 */
export default Backbone.Model.extend({
  defaults: {
    path: "",
    absolutePath: "",
  },

  getPath() {
    return this.get("path");
  },

  getAbsolutePath() {
    return this.get("absolutePath");
  },
});
