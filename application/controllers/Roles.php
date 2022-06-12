<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends CI_Controller {
    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('general_helper');
        $this->load->library('grocery_CRUD');
        $this->template->write_view('sidenavs', 'template/default_sidenavs', true);
        $this->template->write_view('navs', 'template/default_topnavs.php', true);
    }

    function index() {
        $this->template->write('title', 'Roles', TRUE);
        $this->template->write('header', 'Roles');

        $crud = new Grocery_CRUD();
        $crud->set_table('roles');
        $crud->set_subject('Role');

        $columns = ['Name','Description'];
        $fields = ['Name','Description','Can_List','Can_Read','Can_Add','Can_Edit','Can_Delete'];

        if ('read' == $crud->getState()) {
            $columns = ['Name','Description','Can_List','Can_Read','Can_Add','Can_Edit','Can_Delete'];
        } elseif ('edit' == $crud->getState() || 'update' == $crud->getState()) {
            $fields = ['Name','Description','Can_List','Can_Read','Can_Add','Can_Edit','Can_Delete'];
        }


	$crud->display_as('Name','Role Name');
	$crud->display_as('Description','Role Description');
	$crud->display_as('Can_List','Can List');
        $crud->display_as('Can_Read','Can Read');
        $crud->display_as('Can_Add','Can Add');
	$crud->display_as('Can_Edit','Can Edit');
	$crud->display_as('Can_Delete','Can Delete');


        $crud->columns($columns);
        $crud->fields($fields);

        if(!can_list(get_class($this))) $crud->unset_list();
        if(!can_read(get_class($this))) $crud->unset_read();
        if(!can_add(get_class($this)))  $crud->unset_add();
        if(!can_edit(get_class($this))) $crud->unset_edit();
        if(!can_delete(get_class($this))) $crud->unset_delete();

        $crud->required_fields('Name');

        $this->template->write_view('content', 'example', $crud->render());
        $this->template->render();
    }

}
