export default class AssetService {
  constructor() {
    this.uploadPath = '/s/grapesjsbuilder/upload';
    this.deletePath = '/s/grapesjsbuilder/delete';
    this.assetsPath = '/s/grapesjsbuilder/media';
    this.assetsPage = 1;
    this.totalPages = null;
  }

  /**
   * Get the path for uploading assets
   * @returns {string} The upload path
   */
  getUploadPath() {
    return this.uploadPath;
  }

  /**
   * Get the path for deleting assets
   * @returns {string} The delete path
   */
  getDeletePath() {
    return this.deletePath;
  };


  /**
   * Get assets path with current page
   * @returns {string}
   */
  getAssetsPath() {
    return `${this.assetsPath}?page=${this.assetsPage}`;
  }

  /**
   * Fetch assets from the server
   * @param {string} assetsPath
   * @returns {Promise<Object>}
   */
  async fetchAssets(assetsPath) {
    try {
      const response = await fetch(assetsPath);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      const result = await response.json();
      if (result.totalPages !== undefined) {
        this.totalPages = result.totalPages;
      }
      return result;
    } catch (error) {
      console.error('Error fetching assets:', error);
      throw error;
    }
  }

  /**
   * Get assets for the first page
   * @returns {Promise<Object>}
   */
  async getAssetsXhr() {
    this.assetsPage = 1;
    return this.fetchAssets(this.getAssetsPath());
  }

  /**
   * Get assets for the next page
   * @returns {Promise<Object>|null}
   */
  async getAssetsNextPageXhr() {
    if (this.hasLoadedAllAssets()) {
      return null;
    }
    this.assetsPage++;
    return this.fetchAssets(this.getAssetsPath());
  }

  /**
   * Check if all assets have been loaded
   * @returns {boolean}
   */
  hasLoadedAllAssets() {
    return this.totalPages !== null && this.assetsPage >= this.totalPages;
  }
}
