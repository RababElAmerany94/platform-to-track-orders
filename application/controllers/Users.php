<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		$this->load->helper('general_helper');
		if (!is_user_logged_in())
			redirect('login');

		$this->load->database();
		$this->load->library('grocery_CRUD');
		$this->load->library('form_validation');

		$this->template->write_view('sidenavs', 'template/default_sidenavs', true);
		$this->template->write_view('navs', 'template/default_topnavs.php', true);
	}

	function index()
	{
		$this->template->write('title', 'Users', TRUE);
		$this->template->write('header', 'Users');

		$crud = new grocery_CRUD();
		$crud->set_table('users');
		$crud->set_subject('User');

		$fields = ['username', 'password', 'Id_Role', 'full_name', 'email', 'phone_number', 'phone_number2', 'address', 'city', 'zipcode'];
		$crud->columns('username', 'Id_Role','full_name', 'email', 'phone_number','phone_number2','city', 'last_login');
		$crud->display_as('username', 'Username')
			->display_as('password', 'Change Password')
 			->display_as('Id_Role', 'Role')
			->display_as('full_name', 'Full Name')
			->display_as('email', 'Email')
			->display_as('phone_number', 'Phone')
			->display_as('phone_number2', 'Phone 2')
			->display_as('address', 'Address')
			->display_as('city', 'City')
			->display_as('zipcode', 'Zip code');

		$crud->set_relation('Id_Role', 'roles', 'Name');
		$required_fields = ['username', 'full_name', 'email', 'phone_number'];
		$crud->required_fields($required_fields);
		$crud->callback_read_field('phone_number', function ($value, $primary_key) {
            return '<a href="tel:'.$value.'" class="btn btn-success btn-large fa fa-phone">&nbsp;'.$value.'</a>';
        });
        $crud->callback_read_field('phone_number2', function ($value, $primary_key) {
            return '<a href="tel:'.$value.'" class="btn btn-success btn-large fa fa-phone">&nbsp;'.$value.'</a>';
        });

        if ($crud->getState() != 'edit' && $crud->getState() != 'update') {
            if (!can_list(get_class($this))) $crud->unset_list();
            if (!can_read(get_class($this))) $crud->unset_read();
            if (!can_add(get_class($this))) $crud->unset_add();
            if (!can_edit(get_class($this))) $crud->unset_edit();
            if (!can_delete(get_class($this))) $crud->unset_delete();
        }

        switch ($crud->getState()) {
            case 'read':
		$fields = ['username', 'Id_Role', 'full_name', 'email', 'phone_number', 'phone_number2', 'address', 'city', 'zipcode','last_login','last_login_ip'];
                break;
            case 'edit':
	    case 'update':
		$fields = ['username', 'password', 'Id_Role', 'full_name', 'email', 'phone_number', 'phone_number2', 'address', 'city', 'zipcode'];
                $crud->required_fields('username', 'full_name');
                $this->session->set_flashdata('edit_user_id', $crud->getStateInfo()->primary_key);

                if ($this->session->userdata()['user_id'] == $crud->getStateInfo()->primary_key && !can_edit(get_class($this))) {
		    $fields = ['password', 'full_name', 'email', 'phone_number', 'phone_number2', 'address', 'city', 'zipcode'];
                    $crud->unset_back_to_list();
                } else {
                    if (!can_list(get_class($this))) $crud->unset_list();
                    if (!can_read(get_class($this))) $crud->unset_read();
                    if (!can_add(get_class($this))) $crud->unset_add();
                    if (!can_edit(get_class($this))) $crud->unset_edit();
                    if (!can_delete(get_class($this))) $crud->unset_delete();
                }
                break;
            case 'add':
                $crud->required_fields('username', 'password', 'full_name' , 'phone_number');
                break;
        }
        if('read' == $crud->getState())
		{
			$crud->unset_fields('password');
		}

	$crud->fields($fields);

	$crud->callback_field('password', array($this, 'set_password_input_to_empty'));
	$crud->callback_before_insert(array($this, 'encrypt_password_before_insert_callback'));
	$crud->callback_before_update(array($this, 'encrypt_password_callback'));

	$this->template->write_view('content', 'example', $crud->render());
	$this->template->render();
	}

	function encrypt_password_before_insert_callback($post_array)
	{
		$post_array['password'] = pwd_hash(trim(strip_tags($post_array['password'])), 'md5', 2);

		return $post_array;
	}

	function encrypt_password_callback($post_array)
	{
		//Encrypt password only if is not empty. Else don't change the password to an empty field
		$this->db->where('user_id', $this->session->flashdata('edit_user_id'));
		$user = $this->db->get('users')->row();

		if ($user) {
			if (!empty($post_array['password']) && pwd_hash(trim(strip_tags($post_array['password'])), 'md5', 2) != $user->password) {
				$post_array['password'] = pwd_hash(trim(strip_tags($post_array['password'])), 'md5', 2);
			} else {
				$post_array['password'] = $user->password;
			}
		}

		return $post_array;
	}

	function set_password_input_to_empty($value = '', $primary_key = null)
	{
		return '<input id="field-password" class="form-control" name="password" type="password" value="" style="width: 100%;">';
	}

	function set_default_role($value = '', $primary_key = null)
	{
		return form_dropdown(
			'Id_Role',
			array_combine($this->config->item('ROLES'), $this->config->item('ROLES')),
			$this->config->item('DEFAULT_ROLE'),
			[
				'id' => 'field-role',
				'class' => 'chosen-select'
			]
		);
	}
}
