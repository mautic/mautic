CKEditor Image Browser plugin
=============================

**imagebrowser** is a `CKEditor <http://ckeditor.com/>`_ plugin that allows images on the server to be browsed and picked
for inclusion into the editor's contents.

This plugin integrates with the **image** plugin (part of CKEditor),
by making it provide a **Browse Server** button in the Image dialog window (`screenshot here <http://ckeditor.com/sites/default/files/styles/large/public/image/image_manager.png>`_).

The way you use it is very similar to `imageGetJson <http://imperavi.com/redactor/docs/settings/#set_imageGetJson>`_ in `Redactor <http://imperavi.com/redactor/>`_
- you only need to provide a list of images in a JSON format, and the image browser will take care of the rest.

In fact, it uses the same data format as Redactor, allowing for an easy transition between the two editors.

Installation
------------

Copy the whole contents of this repository into a new ``plugins/imagebrowser`` directory in your CKEditor install.

Make sure you're using the **Standard** or **Full** `CKEditor packages <http://ckeditor.com/download>`_.
The **Basic** package lacks an in-built "File Browser" plugin, which this plugin depends on.
You can also use a `Custom CKEditor package <http://ckeditor.com/builder>`_, if you build it with "File Browser" plugin support.

Usage
-----

Enable the plugin by adding it to `extraPlugins` and specify the `imageBrowser_listUrl` parameter::

	CKEDITOR.replace('textareaId', {
		"extraPlugins": "imagebrowser",
		"imageBrowser_listUrl": "/path/to/images_list.json"
	});

The **imageBrowser_listUrl** configuration parameter points to a URL that lists the server's images in a JSON format.

Example::

	[
		{
			"image": "/image1_200x150.jpg",
			"thumb": "/image1_thumb.jpg",
			"folder": "Small"
		},
		{
			"image": "/image2_200x150.jpg",
			"thumb": "/image2_thumb.jpg",
			"folder": "Small"
		},

		{
			"image": "/image1_full.jpg",
			"thumb": "/image1_thumb.jpg",
			"folder": "Large"
		},
		{
			"image": "/image2_full.jpg",
			"thumb": "/image2_thumb.jpg",
			"folder": "Large"
		}
	]

The above says that there are 2 image directories ("Small" and "Large") with 2 files in each of them.

The **image** field is the relative/absolute path being used when the image gets put into the editor's contents.

The **thumb** field is *optional*. It specifies the relative/absolute path to the image's thumbnail (for preview purposes).
If omitted, the value of **image** is used as a thumbnail.

The **folder** field is *optional*. If omitted, the image list will not be split into folders.
