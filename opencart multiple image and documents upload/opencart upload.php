	public function index() {
		if(isset($this->request->files['image'])){

			//rearrange the array, make it ready to upload.
			$image_array = $this->rearrangeImageArray($this->request->files['image']);

			//upload the image
			$data['upload_image'] = $this->session->data['upload_image'] = $this->uploadMultiplePhotos($image_array);

			//initialize arrays, otherwise saves as string
			$data['image_names'] = array();
			$this->session->data['image_names'] = array();
			$this->session->data['upload_error_image'] = array();
			$data['upload_error_image'] = array();

			//add filenames to session to show in the next page
			foreach($image_array as $key => $image_name){
				$data['image_names'][$key] = $image_name['name'];
				$this->session->data['image_names'][$key] = $image_name['name'];
			}

//                echo '<pre>', print_r($data['upload_image']), '</pre>';
//                echo '<br>';
//                echo '<br>';
//                echo '<br>';
//                echo '<pre>', print_r($data['image_names']), '</pre>';
//                exit;

			//check if there are any errors on upload
			foreach ($this->session->data['upload_image'] as $key => $check_fail){
				if(isset($check_fail['error'])){
					$this->session->data['upload_error_image'][$key] = "Error uploading "
						.$data['image_names'][$key];
					$data['upload_error_image'][$key] ="Error uploading "
						.$data['image_names'][$key];
				}
			}
		}

		if(isset($this->request->files['docs'])){
			//rearrange the array, make it ready to upload.
			$docs_array = $this->rearrangeImageArray($this->request->files['docs']);

			//upload the file
			$data['upload_docs'] = $this->session->data['upload_docs'] = $this->uploadDocs($docs_array);

			//initialize arrays, otherwise saves as string
			$data['docs_names'] = array();
			$this->session->data['docs_names'] = array();
			$this->session->data['upload_error_docs'] = array();
			$data['upload_error_docs'] = array();


			//add filenames to session to show in the next page
			foreach($docs_array as $key => $docs_name){
				$data['docs_names'][$key] = $docs_name['name'];
				$this->session->data['docs_names'][$key] = $docs_name['name'];
			}

			//check if there are any errors on upload
			foreach ($this->session->data['upload_docs'] as $key => $check_fail){
				if(isset($check_fail['error'])){
					$this->session->data['upload_error_docs'][$key] = "Error uploading "
						.$data['docs_names'][$key];
					$data['upload_error_docs'][$key] ="Error uploading "
						.$data['docs_names'][$key];
				}
			}
		}
	}	

	public function rearrangeImageArray($array){
        $image_array = array();
        $file_count = count($array['name']);
        $file_keys = array_keys($array);
        for ($i=0; $i<$file_count; $i++)
        {
            foreach ($file_keys as $key)
            {
                $image_array[$i][$key] = $array[$key][$i];
            }
        }
        return $image_array;
    }

    public function uploadMultiplePhotos($image_array){
	    $this->load->language('tool/upload');

        $json = array();

        $check_file = 0;
        $check_empty = 0;

        foreach ($image_array as $is_file){
            if(!is_file($is_file['tmp_name'])){
                $check_file = 1;
            }
        }

        foreach ($image_array as $empty){
            if(empty($empty['name'])){
                $check_empty = 1;
            }
        }

        if ($check_empty == 0 && $check_file == 0) {
            foreach ($image_array as $key => $image_file) {
                $tmp_name[$key] = $image_file['tmp_name'];

                // Sanitize the filename
                $filenames[$key] = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode
                ($image_file['name'], ENT_QUOTES, 'UTF-8')));

                // Validate the filename length
                if ((utf8_strlen($filenames[$key]) < 3) || (utf8_strlen($filenames[$key]) > 64)) {
                    $json['error'] = $this->language->get('error_filename');
                }

                // Allowed file extension types
                $allowed = array();

                $extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_ext_allowed'));

                $filetypes = explode("\n", $extension_allowed);

                foreach ($filetypes as $filetype) {
                    $allowed[] = trim($filetype);
                }

                if (!in_array(strtolower(substr(strrchr($filenames[$key], '.'), 1)), $allowed)) {
                    $json['error'] = $this->language->get('error_filetype');
                }

                // Allowed file mime types
                $allowed = array();

                $mime_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_mime_allowed'));

                $filetypes = explode("\n", $mime_allowed);

                foreach ($filetypes as $filetype) {
                    $allowed[] = trim($filetype);
                }

                if (!in_array($image_file['type'], $allowed)) {
                    $json['error'] = $this->language->get('error_filetype');
                }

                // Check to see if any PHP files are trying to be uploaded
                $content = file_get_contents($image_file['tmp_name']);

                if (preg_match('/\<\?php/i', $content)) {
                    $json['error'] = $this->language->get('error_filetype');
                }

                // Return any upload error
                if ($image_file['error'] != UPLOAD_ERR_OK) {
                    $json['error'] = $this->language->get('error_upload_' . $image_file['error']);
                }
            }
        } else {
            $json['error'] = $this->language->get('error_upload');
        }

        if (!$json) {
            foreach ($filenames as $key => $file){
                $ext = pathinfo($file);
                $file = $ext['filename'] . '.' . md5(mt_rand()) . "." . $ext['extension'];

                move_uploaded_file($tmp_name[$key], DIR_UPLOAD_TEMP . $file);

                // Hide the uploaded file name so people can not link to it directly.
                $this->load->model('tool/upload');

                $json[$key]['tmp_name'] = $tmp_name[$key];
                $json[$key]['code'] = $this->model_tool_upload->addUpload($filenames[$key], $file);
                $json[$key]['file_location'] = DIR_UPLOAD_TEMP . $file;
                $json[$key]['new_file_name'] = $file;
                $json[$key]['success'] = $this->language->get('text_upload');
            }
        }
        return $json;
    }
    
	public function uploadDocs($docs_array)
    {
        $this->load->language('tool/upload');

        $json = array();

        $check_file = 0;
        $check_empty = 0;

        foreach ($docs_array as $is_file) {
            if (!is_file($is_file['tmp_name'])) {
                $check_file = 1;
            }
        }

        foreach ($docs_array as $empty) {
            if (empty($empty['name'])) {
                $check_empty = 1;
            }
        }

        if ($check_empty == 0 && $check_file == 0) {
            foreach ($docs_array as $key => $docs_file) {
                $tmp_name[$key] = $docs_file['tmp_name'];

                // Sanitize the filename
                $filenames[$key] = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode
                ($docs_file['name'], ENT_QUOTES, 'UTF-8')));

                // Validate the filename length
                if ((utf8_strlen($filenames[$key]) < 3) || (utf8_strlen($filenames[$key]) > 64)) {
                    $json['error'] = $this->language->get('error_filename');
                }

                // Check to see if any PHP files are trying to be uploaded
                $content = file_get_contents($docs_file['tmp_name']);

                if (preg_match('/\<\?php/i', $content)) {
                    $json['error'] = $this->language->get('error_filetype');
                }

                // Return any upload error
                if ($docs_file['error'] != UPLOAD_ERR_OK) {
                    $json['error'] = $this->language->get('error_upload_' . $docs_file['error']);
                }
            }
        } else {
            $json['error'] = $this->language->get('error_upload');
        }

        if (!$json) {
            foreach ($filenames as $key => $file) {
                $ext = pathinfo($file);
                $file = $ext['filename'] . '.' . md5(mt_rand()) . "." . $ext['extension'];

                move_uploaded_file($tmp_name[$key], DIR_UPLOAD_TEMP . $file);

                // Hide the uploaded file name so people can not link to it directly.
                $this->load->model('tool/upload');

                $json[$key]['tmp_name'] = $tmp_name[$key];
                $json[$key]['code'] = $this->model_tool_upload->addUpload($filenames[$key], $file);
                $json[$key]['file_location'] = DIR_UPLOAD_TEMP . $file;
                $json[$key]['new_file_name'] = $file;
                $json[$key]['success'] = $this->language->get('text_upload');
            }
        }
        return $json;
    }