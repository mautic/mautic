import Backbone from "backbone";
import AssetImage from "./AssetImage";
import AssetImageView from "./../view/AssetImageView";
import TypeableCollection from "domain_abstract/model/TypeableCollection";
import AssetDirectory from "./AssetDirectory";
import DirectoryView from "../view/DirectoryView";

export default Backbone.Collection.extend(TypeableCollection).extend({
  comparator: (model) => {
    if (model.get('type') === 'image-dir') {
      return [0, model.get('path')]
    }

    return [1, model.get('src')];
  },
  types: [
    {
      id: "image",
      model: AssetImage,
      view: AssetImageView,
      isType(value) {
        if (typeof value == "string") {
          return {
            type: "image",
            src: value,
          };
        }
        return value;
      },
    },
    {
      id: "image-dir",
      model: AssetDirectory,
      view: DirectoryView,
      isType(value) {
        if (typeof value == "string") {
          return {
            type: "image",
            src: value,
          };
        }
        return value;
      },
    },
  ],
});
