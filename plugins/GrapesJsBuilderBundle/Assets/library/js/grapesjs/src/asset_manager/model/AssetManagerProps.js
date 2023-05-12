import { result } from "underscore";
import { Model } from "common";

export default class AssetManagerProps extends Model {
  defaults() {
    return {
      isLoading: true,
      activeDirectory: "root",
      assetsPath: null,
      newDirectoryPath: null,
      canManageFolders: false,
    };
  }

  toggleLoading() {
    this.isLoading = !this.isLoading;
  }
}
