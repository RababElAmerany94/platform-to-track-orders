<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('general_helper');
		$this->load->library('form_validation');
		$this->load->database();
		$this->load->dbutil();
	}
	
	function index()
	{
		$this->template->set_template('login');
		$this->template->write('title', 'Authentification', true);
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		$error = 'Bad username or password.';
		
		if ('POST' == $this->input->server('REQUEST_METHOD') && (!empty($username)) && (!empty($password))) {
			$this->db->where("username", $username);
			$user = $this->db->get("users")->row();
			
			if ($user && pwd_verify($password, $user->password)) {
				$this->db->where("Id_Role", $user->Id_Role);
				$role = $this->db->get("roles")->row();
				
				$can_list = explode(",", $role->Can_List);
				$can_read = explode(",", $role->Can_Read);
				$can_add = explode(",", $role->Can_Add);
				$can_edit = explode(",", $role->Can_Edit);
				$can_delete = explode(",", $role->Can_Delete);
				
				array_walk($can_list, "canonize_class");
				array_walk($can_read, "canonize_class");
				array_walk($can_add, "canonize_class");
				array_walk($can_edit, "canonize_class");
				array_walk($can_delete, "canonize_class");
				
				$user_data = [
					'user_id'    => $user->user_id,
					'Id_Role'    => $user->Id_Role,
					'username'   => $user->username,
					'password'   => $user->password,
					'full_name'  => $user->full_name,
					'email'      => $user->email,
					'login_ip'   => $_SERVER["REMOTE_ADDR"],
					'logged_in'  => true,
					'can_list'   => $can_list,
					'can_read'   => $can_read,
					'can_add'    => $can_add,
					'can_edit'   => $can_edit,
					'can_delete' => $can_delete,
				];
				
				$this->session->set_userdata($user_data);
				$this->db->set('last_login_ip', '"' . $_SERVER["REMOTE_ADDR"] . '"', false);
				$this->db->where('user_id', $user->user_id);
				$this->db->update('users');
				if ($user->Id_Role == 1) {
					redirect('dashboard');
				}
				if ($user->Id_Role == 2) {
					redirect('Orders');
				}
			} else {
				$this->template->write('error', $error, true);
			}
		}
		$this->template->render();
	}
	
	/*
	 * User logout
	 */
	public function logout()
	{
		$this->session->unset_userdata('user_logged_in');
		$this->session->sess_destroy();
		redirect('login');
	}
}
