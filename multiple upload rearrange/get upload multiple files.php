<?php
	$image_array = array();
	$file_count = count($this->request->files['image']['name']);
	$file_keys = array_keys($this->request->files['image']);
	
	for ($i=0; $i<$file_count; $i++)
	{
		foreach ($file_keys as $key)
		{
			$image_array[$i][$key] = $this->request->files['image'][$key][$i];
		}
	}
	return $image_array;