<?php defined('BASEPATH') OR exit('No direct script access allowed');

require __DIR__ . '/BaseController.php';

class Shipments extends BaseController
{
	function __construct()
	{
		parent::__construct();
		$this->load->helper('general_helper');
		$this->load->database();
		$this->load->library('grocery_CRUD');
		$this->template->write_view('sidenavs', 'template/default_sidenavs', true);
		$this->template->write_view('navs', 'template/default_topnavs.php', true);
	}

	function index()
	{
		$userdata = $this->session->userdata();
		$language = $this->config->item('DEFAULT_LANGUAGE');

		if (isset($userdata) && $userdata['Id_Role'] == 2 && isset($userdata['language']) && in_array($language, $this->config->item('LANGUAGES'))) {
			$language = $userdata['language'];
		}

		$crud = new grocery_CRUD();
		$crud->set_language($language);
		$crud->set_table('shipments');
		$crud->set_subject('Shipment');

		$columns = ['user_id', 'ProductId', 'ShipmentDate', 'InitialQuantity', 'RemainingQuantity'];
		$fields  = ['user_id', 'ProductId', 'TrackingNumber', 'ShipmentDate', 'InitialQuantity'];

		if ('read' == $crud->getState()) {
			$columns = ['user_id', 'ProductId', 'TrackingNumber', 'ShipmentDate', 'InitialQuantity', 'RemainingQuantity'];
		} elseif ('edit' == $crud->getState() || 'update' == $crud->getState()) {
			$fields = ['user_id', 'ProductId', 'TrackingNumber', 'ShipmentDate', 'InitialQuantity', 'RemainingQuantity'];
		}

		$crud->set_relation('user_id', 'users', 'full_name', ['Id_Role' => settings('app_DeliveryAgentRoleId')]);
		$crud->set_relation('ProductId', 'products', 'Name');

		$crud->display_as("ShipmentId", "Shipment Id");
		$crud->display_as("user_id", $this->lang->line('Delivery Agent'));
		$crud->display_as("ProductId", $this->lang->line('Product'));
		$crud->display_as("TrackingNumber", "Tracking No.");
		$crud->display_as("ShipmentDate", $this->lang->line('Shipment Date'));
		$crud->display_as("InitialQuantity", $this->lang->line('Initial Quantity'));
		$crud->display_as("RemainingQuantity", $this->lang->line('Remaining Quantity'));

		$crud->columns($columns);
		$crud->fields($fields);

		if (!can_list(get_class($this))) $crud->unset_list();
		if (!can_read(get_class($this))) $crud->unset_read();
		if (!can_add(get_class($this))) $crud->unset_add();
		if (!can_edit(get_class($this))) $crud->unset_edit();
		if (!can_delete(get_class($this))) $crud->unset_delete();

		if ($userdata["Id_Role"] == settings('app_DeliveryAgentRoleId')) {
			$crud->where(['shipments.user_id' => $userdata['user_id']]);
		}

		$crud->callback_after_insert([$this, 'callback_after_change']);
		$crud->required_fields('user_id', 'ProductId', 'ShipmentDate', 'InitialQuantity', 'RemainingQuantity');

		$this->template->write('title', $this->lang->line('Shipments to Delivery Agents'), true);
		$this->template->write('header', $this->lang->line('Shipments to Delivery Agents'));
		$this->template->write_view('content', 'example', $crud->render());
		$this->template->render();
	}

	function callback_after_change($post_array, $primary_key)
	{
		$query = $this->db->get_where('shipments', ['ShipmentId' => $primary_key])->row_array();

		$variable = $query['InitialQuantity'];
		$this->db->set('RemainingQuantity', $variable);
		$this->db->where('ShipmentId', $primary_key);
		$this->db->update('shipments');
	}

}
