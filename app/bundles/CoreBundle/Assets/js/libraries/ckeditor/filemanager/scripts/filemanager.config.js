{
    "_comment": "IMPORTANT : go to the wiki page to know about options configuration https://github.com/simogeo/Filemanager/wiki/Filemanager-configuration-file",
    "options": {
        "culture": "en",
        "lang": "php",
        "theme": "mautic",
        "defaultViewMode": "grid",
        "autoload": true,
        "showFullPath": false,
        "showTitleAttr": false,
        "browseOnly": false,
        "showConfirmation": false,
        "showThumbs": true,
        "generateThumbnails": true,
        "searchBox": true,
        "listFiles": true,
        "fileSorting": "default",
        "chars_only_latin": true,
        "dateFormat": "d M Y H:i",
        "serverRoot": true,
        "fileRoot": "/",
        "baseUrl": false,
        "logger": false,
        "capabilities": ["select", "download", "rename", "delete", "replace"],
        "plugins": []
    },
    "security": {
        "allowFolderDownload": false,
        "allowChangeExtensions": false,
        "allowNoExtension": false,
            "uploadPolicy": "DISALLOW_ALL",
            "uploadRestrictions": [
            "jpg",
            "jpe",
            "jpeg",
            "gif",
            "png",
            "svg",
            "css",
            "js"
        ]
    },
    "upload": {
            "multiple": true,
            "number": 10,
            "overwrite": false,
            "imagesOnly": false,
            "fileSizeLimit": 16
    },
    "exclude": {
        "unallowed_files": [
            ".htaccess",
            "web.config"
        ],
            "unallowed_dirs": [
            "_thumbs",
            ".CDN_ACCESS_LOGS",
            "cloudservers"
        ],
            "unallowed_files_REGEXP": "/^\\./",
            "unallowed_dirs_REGEXP": "/^\\./"
    },
    "images": {
        "imagesExt": [
            "jpg",
            "jpe",
            "jpeg",
            "gif",
            "png",
            "svg"
        ],
            "resize": {
            "enabled":true,
                "maxWidth": 1280,
                "maxHeight": 1024
        }
    },
    "videos": {
        "showVideoPlayer": true,
            "videosExt": [
            "ogv",
            "mp4",
            "webm",
            "m4v"
        ],
            "videosPlayerWidth": 400,
            "videosPlayerHeight": 222
    },
    "audios": {
        "showAudioPlayer": true,
            "audiosExt": [
            "ogg",
            "mp3",
            "wav"
        ]
    },
    "edit": {
        "enabled": false,
            "lineNumbers": true,
            "lineWrapping": true,
            "codeHighlight": false,
            "theme": "elegant",
            "editExt": [
            "txt",
            "csv"
        ]
    },
    "customScrollbar": {
    	"enabled": false,
    	"theme": "inset-2-dark",
    	"button": true
    },
    "extras": {
        "extra_js": [],
            "extra_js_async": true
    },
    "icons": {
        "path": "images/fileicons/",
            "directory": "_Open.png",
            "default": "default.png"
    },
    "url": "https://github.com/simogeo/Filemanager"
}
