import Asset from "./Asset";

export default class AssetDirectory extends Asset {
  defaults() {
    return {
      path: "",
      icon: "",
      type: "image-dir",
    };
  }
}
