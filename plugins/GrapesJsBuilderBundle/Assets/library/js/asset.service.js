export default class AssetService {
  /**
   * Get a list of all existing assets (e.g. images)
   * to display in the assets manager, and the config
   *
   * @returns array
   */
  static getAssets() {
    const textareaAssets = mQuery("textarea#grapesjsbuilder_assets");
    const assetsPath = textareaAssets.data("assets");
    const uploadPath = textareaAssets.data("upload");
    const deletePath = textareaAssets.data("delete");
    const newDirectoryPath = textareaAssets.data("directory");
    const canManageFolders = textareaAssets.data("dirmanage");

    return {
      conf: {
        newDirectoryPath,
        assetsPath,
        uploadPath,
        deletePath,
        canManageFolders,
      },
    };
  }
}
