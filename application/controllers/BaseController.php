<?php

class BaseController extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper('general_helper');

		if (!is_user_logged_in()) {
			redirect('login');
		}

		$userdata = $this->session->userdata();
		$language = $this->config->item('DEFAULT_LANGUAGE');

		if (isset($userdata) && $userdata['Id_Role'] == 2 && isset($userdata['language']) && in_array($language, $this->config->item('LANGUAGES'))) {
			$language = $userdata['language'];
		}

		$this->lang->load('all', $language);
	}
}