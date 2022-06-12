<?php


class Language extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$userdata = $this->session->userdata();
		if ($userdata['Id_Role'] != 2) {
			redirect('/');
		}

		//Load general helper
		$this->load->helper('general_helper');

		//Load form helper
		$this->load->helper('form');

		//Load user_agent library
		$this->load->library('user_agent');
	}

	public function index()
	{
		//Get the selected language
		$language = $this->input->get('lang');

		$referrer = '/';
		if ($this->agent->is_referral()) {
			$referrer = $this->agent->referrer();
		}

		//Choose language file according to selected language
		if (!in_array($language, $this->config->item('LANGUAGES'))) {
			$language = $this->config->item('DEFAULT_LANGUAGE');
		}

		$userdata             = $this->session->userdata();
		$userdata['language'] = $language;

		$this->session->set_userdata($userdata);
		$this->lang->load("all_lang", $language);

		redirect($referrer);
		return;
	}
}