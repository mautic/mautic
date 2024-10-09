export default class AssetService {
  static assetsPage = 1;
  static totalPages = null;

  /**
   * Get initial config for asset manager
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

  static getAssetsPath() {
    const textareaAssets = mQuery('#grapesjsbuilder_assets');
    return `${textareaAssets.data('assets')}?page=${AssetService.assetsPage}`;
  }

  static fetchAssets(assetsPath, onSuccess, onError) {
    return mQuery.get(assetsPath)
        .done((result) => {
          if (result.totalPages !== undefined) {
            AssetService.totalPages = result.totalPages;
          }
          if (typeof onSuccess === 'function') {
            onSuccess(result);
          }
        })
        .fail((error) => {
          if (typeof onError === 'function') {
            onError(error);
          }
        });
  }

  static getAssetsXhr(onSuccess, onError) {
    AssetService.assetsPage = 1;
    const assetsPath = AssetService.getAssetsPath();
    return AssetService.fetchAssets(assetsPath, onSuccess, onError);
  }

  static getAssetsNextPageXhr(onSuccess, onError) {
    if (!AssetService.totalPages || AssetService.assetsPage >= AssetService.totalPages) {
      return;
    }
    AssetService.assetsPage++;
    const assetsPath = AssetService.getAssetsPath();
    return AssetService.fetchAssets(assetsPath, onSuccess, onError);
  }
}
