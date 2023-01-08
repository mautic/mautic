export default class AssetService {

  /**
   * Get a list of all existing assets (e.g. images)
   * to display in the assets manager, and the config
   *
   * @returns array
   */
  static getAssets() {
    const textareaAssets = mQuery('textarea#grapesjsbuilder_assets');
    const files = textareaAssets.val() ? JSON.parse(textareaAssets.val()) : [];
    const uploadPath = textareaAssets.data('upload');
    const deletePath = textareaAssets.data('delete');
    return {
      files,
      conf: {
        uploadPath,
        deletePath,
      },
    };
  }
}
