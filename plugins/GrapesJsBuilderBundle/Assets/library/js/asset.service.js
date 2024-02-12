export default class AssetService {

  /**
   * Get a list of all existing assets (e.g. images)
   * to display in the assets manager, and the config
   *
   * @returns object
   */
  static getAssetsConfig() {
    const textareaAssets = mQuery('#grapesjsbuilder_assets');
    const uploadPath = textareaAssets.data('upload');
    const deletePath = textareaAssets.data('delete');
    return {
      files: [],
      conf: {
        uploadPath,
        deletePath,
      },
    };
  }

  /**
   * Get a list of all existing assets (e.g. images)
   * to display in the assets manager, and the config
   *
   * @returns jqXHR
   */
  static getAssetsXhr(onSuccess) {
    const textareaAssets = mQuery('#grapesjsbuilder_assets');
    const assetsPath = textareaAssets.data('assets');
    return mQuery.get(assetsPath, onSuccess);
  }
}
