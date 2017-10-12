<?php
/**
*	Filemanager PHP RSC plugin class.
*
*	filemanager.rsc.class.php
*	class for the filemanager.php connector which utilizes the Rackspace Cloud Files API
*	instead of the local filesystem
*
*	@license	MIT License
*	@author		Alan Blount <alan (at) zeroasterisk (dot) com>
*	@author		Riaan Los <mail (at) riaanlos (dot) nl>
*	@author		Simon Georget <simon (at) linea21 (dot) com>
*	@copyright	Authors
*/
class FilemanagerRSC extends Filemanager
{
    public function __construct($config)
    {
        $return = parent::__construct($config);
        require_once 'cloudfiles.php';
        $auth = new CF_Authentication($this->config['rsc-username'], $this->config['rsc-apikey']);
        $auth->authenticate();
        $this->conn = new CF_Connection($auth);
        if ($this->config['rsc-ssl_use_cabundle']) {
            $this->conn->ssl_use_cabundle();
        }

        return $return;
    }

    public function getinfo()
    {
        $object = $this->get_object();
        if (isset($object->name)) {
            $object = $this->get_file_info($object);

            return [
                'Path'       => $object->path,
                'Filename'   => $object->name,
                'File Type'  => $object->filetype,
                'Preview'    => $object->preview,
                'Properties' => $object->properties,
                'Error'      => '',
                'Code'       => 0,
                ];
        }

        $container = $this->get_container();
        if (isset($container->name)) {
            return [
                'Path'       => $container->path,
                'Filename'   => $container->name,
                'File Type'  => 'dir',
                'Preview'    => $this->config['icons']['path'].$this->config['icons']['directory'],
                'Properties' => [
                    'Date Created'  => null,
                    'Date Modified' => null,
                    'Height'        => null,
                    'Width'         => null,
                    'Size'          => null,
                    ],
                'Error' => '',
                'Code'  => 0,
                ];
        }

        return [];
    }

    public function getfolder()
    {
        $container      = trim($this->get['path'], '/ ');
        $containerParts = explode('/', $container);
        if ($containerParts[0] == 'containers') {
            array_shift($containerParts);
        }
        $array = [];
        if (empty($containerParts) || trim($this->get['path'], '/ ') == 'containers') {
            $containers = $this->conn->list_containers();
            $containers = array_diff($containers, $this->config['unallowed_dirs']);
            foreach ($containers as $container) {
                $array['/containers/'.$container.'/'] = [
                    'Path'       => '/containers/'.$container.'/',
                    'Filename'   => $container,
                    'File Type'  => 'dir',
                    'Preview'    => $this->config['icons']['path'].$this->config['icons']['directory'],
                    'Properties' => [
                        'Date Created'  => null,
                        'Date Modified' => null,
                        'Height'        => null,
                        'Width'         => null,
                        'Size'          => null,
                        ],
                    'Error' => '',
                    'Code'  => 0,
                    ];
            }
        } else {
            $container = array_shift($containerParts);
            $limit     = 0;
            $marker    = null; // last record returned from a dataset
            $prefix    = null; // search term (starts with)
            $path      = null; // pseudo-hierarchical containers
            if (!empty($containerParts)) {
                $path = implode('/', $containerParts);
            }
            $container = $this->conn->get_container($container);
            //$list = $container->list_objects($limit, $marker,  $prefix, $path);
            $objects = $container->get_objects($limit, $marker,  $prefix, $path);
            foreach ($objects as $object) {
                if (!isset($this->params['type']) || (isset($this->params['type']) && strtolower($this->params['type']) == 'images' && in_array(strtolower($object->content_type), $this->config['images']))) {
                    if ($this->config['upload']['imagesonly'] == false || ($this->config['upload']['imagesonly'] == true && in_array(strtolower($object->content_type), $this->config['images']))) {
                        $object              = $this->get_file_info($object);
                        $array[$object->url] = [
                            'Path'       => $object->path,
                            'Filename'   => $object->name,
                            'File Type'  => $object->filetype,
                            'Mime Type'  => $object->content_type,
                            'Preview'    => $object->preview,
                            'Properties' => $object->properties,
                            'Error'      => '',
                            'Code'       => 0,
                            ];
                    }
                }
            }
        }

        return $array;
    }

    public function rename()
    {
        // keep old filename, if missing from new
        $newNameParts = explode('.', $this->get['new']);
        $newNameExt   = $newNameParts[(count($newNameParts) - 1)];
        if (strlen($newNameExt) > 5 || count($newNameParts) == 1) {
            $this->get['new'] .= '.'.array_pop(explode('.', $this->get['old']));
        }
        // get old
        $object = $this->get_object($this->get['old']);
        if (!isset($object->container)) {
            $this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'), $path));
        }
        if (in_array($this->get['new'], $object->container->list_objects())) {
            $this->error(sprintf($this->lang('FILE_ALREADY_EXISTS'), $this->get['new']));

            return false;
        }
        // create to new
        $new               = $object->container->create_object($this->get['new']);
        $new->content_type = $object->content_type;
        $data              = $object->read();
        $new->write($data);
        if (!empty($object->metadata)) {
            $new->metadata = $object->metadata;
            $object->sync_metadata(); // save back to RSC
        }
        $object->container->delete_object($object->name);
        $array = [
            'Error'    => '',
            'Code'     => 0,
            'Old Path' => $object->path,
            'Old Name' => $object->name,
            'New Path' => $new->path,
            'New Name' => $new->name,
            ];

        return $array;
    }

    public function delete()
    {
        $object = $this->get_object();
        if (isset($object->name)) {
            $object->container->delete_object($object->name);

            return [
                'Error' => '',
                'Code'  => 0,
                'Path'  => $this->get['path'],
                ];
        }
        $container = $this->get_container();
        if (isset($container->name)) {
            $list = $container->list_objects(5);
            if (!empty($list)) {
                $this->error('Unable to Delete Container, it is not empty.');

                return false;
            }
            $this->conn->delete_container($container->name);

            return [
                'Error' => '',
                'Code'  => 0,
                'Path'  => $this->get['path'],
                ];
        }
        $this->error(sprintf($this->lang('INVALID_DIRECTORY_OR_FILE')));
    }

    public function add()
    {
        $this->setParams();
        if (!isset($_FILES['newfile']) || !is_uploaded_file($_FILES['newfile']['tmp_name'])) {
            $this->error(sprintf($this->lang('INVALID_FILE_UPLOAD')), true);
        }
        if (($this->config['upload']['size'] != false && is_numeric($this->config['upload']['size'])) && ($_FILES['newfile']['size'] > ($this->config['upload']['size'] * 1024 * 1024))) {
            $this->error(sprintf($this->lang('UPLOAD_FILES_SMALLER_THAN'), $this->config['upload']['size'].'Mb'), true);
        }

        $size = @getimagesize($_FILES['newfile']['tmp_name']);
        if ($this->config['upload']['imagesonly'] || (isset($this->params['type']) && strtolower($this->params['type']) == 'images')) {
            if (empty($size) || !is_array($size)) {
                $this->error(sprintf($this->lang('UPLOAD_IMAGES_ONLY')), true);
            }
            if (!in_array($size[2], [1, 2, 3, 7, 8])) {
                $this->error(sprintf($this->lang('UPLOAD_IMAGES_TYPE_JPEG_GIF_PNG')), true);
            }
        }
        $_FILES['newfile']['name'] = $this->cleanString($_FILES['newfile']['name'], ['.', '-']);

        $container = $this->get_container($this->post['currentpath']);

        if (!$this->config['upload']['overwrite']) {
            $list = $container->list_objects();
            $i    = 0;
            while (in_array($_FILES['newfile']['name'], $list)) {
                ++$i;
                $parts                     = explode('.', $_FILES['newfile']['name']);
                $ext                       = array_pop($parts);
                $parts                     = array_diff($parts, ["copy{$i}", 'copy'.($i - 1)]);
                $parts[]                   = "copy{$i}";
                $parts[]                   = $ext;
                $_FILES['newfile']['name'] = implode('.', $parts);
            }
        }

        $object = $container->create_object($_FILES['newfile']['name']);
        $object->load_from_filename($_FILES['newfile']['tmp_name']);
        // set image details
        if (is_array($size) && count($size) > 1) {
            $object->metadata->height = $object->height = $size[1];
            $object->metadata->width  = $object->width  = $size[0];
            $object->sync_metadata(); // save back to RSC
        }
        unlink($_FILES['newfile']['tmp_name']);

        $response = [
            'Path'  => $this->post['currentpath'],
            'Name'  => $_FILES['newfile']['name'],
            'Error' => '',
            'Code'  => 0,
            ];
        echo '<textarea>'.json_encode($response).'</textarea>';
        die();
    }

    public function addfolder()
    {
        $container      = trim($this->get['path'], '/ ');
        $containerParts = explode('/', $container);
        if ($containerParts[0] == 'containers') {
            array_shift($containerParts);
        }
        if (!empty($containerParts)) {
            $this->error(sprintf($this->lang('UNABLE_TO_CREATE_DIRECTORY'), $newdir));
        }
        $newdir    = $this->cleanString($this->get['name']);
        $container = $this->conn->create_container($newdir);
        $container->make_public(86400 / 2);

        return [
            'Parent' => "/containers/{$container->name}",
            'Name'   => $container->name,
            'Error'  => '',
            'Code'   => 0,
            ];
    }

    public function download()
    {
        $object = $this->get_object();
        if (isset($object->name)) {
            header('Content-type: application/force-download');
            header('Content-Disposition: inline; filename="'.$object->name.'"');
            header('Content-Type: '.$doc->content_type);
            $output = fopen('php://output', 'w');
            $object->stream($output); // stream object content to PHP's output buffer
            fclose($output);

            return true;
        }
        $this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'), $this->get['path']));
    }

    public function preview()
    {
        if (isset($this->get['path']) && file_exists($this->doc_root.$this->get['path'])) {
            header('Content-type: image/'.$ext = pathinfo($this->get['path'], PATHINFO_EXTENSION));
            header('Content-Transfer-Encoding: Binary');
            header('Content-length: '.filesize($this->doc_root.$this->get['path']));
            header('Content-Disposition: inline; filename="'.basename($this->get['path']).'"');
            readfile($this->doc_root.$this->get['path']);
        } else {
            $this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'), $this->get['path']));
        }
    }

    private function get_container($path = null, $showError = false)
    {
        if (empty($path)) {
            $path = $this->get['path'];
        }
        $container      = trim($path, '/ ');
        $containerParts = explode('/', $container);
        if ($containerParts[0] == 'containers') {
            array_shift($containerParts);
        }
        $array = [];
        if (count($containerParts) > 0) {
            $container = $this->conn->get_container(array_shift($containerParts));
            if (isset($container->name)) {
                $container->path = '/containers/'.$container->name;

                return $container;
            }
        }
        if ($showError) {
            $this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'), $path));
        }

        return false;
    }
    private function get_object($path = null, $showError = false)
    {
        if (empty($path)) {
            $path = $this->get['path'];
        }
        $container      = trim($path, '/ ');
        $containerParts = explode('/', $container);
        if ($containerParts[0] == 'containers') {
            array_shift($containerParts);
        }
        $array = [];
        if (count($containerParts) > 1) {
            $container = $this->conn->get_container(array_shift($containerParts));
            $object    = $container->get_object(array_shift($containerParts));
            if (isset($object->name) && isset($object->container->name)) {
                $object->path = '/containers/'.$object->container->name.'/'.$object->name;

                return $object;
            }
        }
        if ($showError) {
            $this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'), $path));
        }

        return false;
    }
    private function get_file_info($object = null)
    {
        if (empty($object) || !is_object($object)) {
            return null;
        }
        // parse into file extension types
        //$object->filetype = array_pop(explode('/', $object->content_type));
        $object->filetype = array_pop(explode('.', $object->name));
        // setup values
        $object->height = null;
        $object->width  = null;
        if (isset($object->metadata->height)) {
            $object->height = $object->metadata->height;
        }
        if (isset($object->metadata->width)) {
            $object->width = $object->metadata->width;
        }
        $preview = $this->config['icons']['path'].$this->config['icons']['default'];
        if (file_exists($this->root.$this->config['icons']['path'].strtolower($object->filetype).'.png')) {
            $preview = $this->config['icons']['path'].strtolower($object->filetype).'.png';
        }
        if (in_array(strtolower($object->filetype), $this->config['images'])) {
            $preview = $object->container->cdn_uri.'/'.$object->name;
            if (empty($object->height) && empty($object->width) && isset($this->config['rsc-getsize']) && !empty($get['rsc-getsize'])) {
                list($width, $height, $type, $attr) = getimagesize($this->doc_root.$path);
                $object->metadata->height           = $object->height           = $height;
                $object->metadata->width            = $object->width            = $width;
                $object->sync_metadata(); // save back to RSC
            }
        }
        $object->filename   = $object->name;
        $object->path       = '/containers/'.$object->container->name.'/'.$object->name;
        $object->url        = $object->container->cdn_uri.'/'.$object->name;
        $object->mimetype   = $object->content_type;
        $object->filemtime  = $object->last_modified;
        $object->preview    = $preview;
        $object->size       = $object->content_length;
        $object->date       = date($this->config['date'], strtotime($object->last_modified));
        $object->properties = [
            'Date Modified' => $object->date,
            'Size'          => $object->size,
            'Height'        => $object->height,
            'Width'         => $object->width,
            ];

        return $object;
    }
}
