<?php defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/BaseController.php';

class Orders extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('general_helper');
        $this->load->helper('url');

        $this->load->database();

        if (PHP_SAPI != 'cli' && $_SERVER['REQUEST_URI'] != "/orders/import") {
            $this->load->library('grocery_CRUD');
            $this->template->write_view('sidenavs', 'template/default_sidenavs', true);
            $this->template->write_view('navs', 'template/default_topnavs.php', true);
        }
    }

    public function index($new_orders = 'all')
    {
        $userdata = $this->session->userdata();
        $language = $this->config->item('DEFAULT_LANGUAGE');

        if (isset($userdata) && $userdata['Id_Role'] == 2 && isset($userdata['language']) && in_array($language, $this->config->item('LANGUAGES'))) {
            $language = $userdata['language'];
        }

        $crud = new grocery_CRUD();
        $crud->set_language($language);
        $crud->set_table('orders');
        $crud->set_subject('Order');
        $crud->order_by('OrderDate', 'DESC');

        $columns = [
            'OrderStatus',
            'OrderNo',
            'OrderDate',
            'RecipientFullName',
            'RecipientCity',
            'user_id',
        ];
        $fields = [
            'user_id',
            'OrderNo',
            'OrderStatus',
            'OrderDate',
            'RecipientFullName',
            'RecipientAddress',
            'RecipientCity',
            'RecipientZipCode',
            'RecipientPhone',
            'OrderTotalPayment',
        ];

        if ('read' == $crud->getState()) {
            $columns = [
                'user_id',
                'OrderNo',
                'OrderStatus',
                'OrderDate',
                'RecipientFullName',
                'RecipientAddress',
                'RecipientCity',
                'RecipientZipCode',
                'RecipientPhone',
                'OrderTotalPayment',
            ];
        } elseif ('edit' == $crud->getState() || 'update' == $crud->getState()) {
            $fields = [
                'user_id',
                'OrderNo',
                'OrderStatus',
                'OrderDate',
                'RecipientFullName',
                'RecipientAddress',
                'RecipientCity',
                'RecipientZipCode',
                'RecipientPhone',
                'OrderTotalPayment',
            ];
        }

        if ($new_orders == 'new') {
            $crud->where('OrderStatus', 'New');
//            $columns = [
            //                'OrderNo',
            //                'OrderDate',
            //                'RecipientFullName',
            //                'RecipientCity',
            //                'user_id',
            //            ];
        }

        $crud->display_as("OrderId", $this->lang->line('Order Id'));
        $crud->display_as("user_id", "Delivery Agent");
        $crud->display_as("OrderNo", $this->lang->line('Order No'));
        $crud->display_as("OrderStatus", $this->lang->line('Order Status'));
        $crud->display_as("OrderDate", $this->lang->line('Order Date'));
        $crud->display_as("RecipientFullName", $this->lang->line('Recipient Full Name'));
        $crud->display_as("RecipientAddress", "Recipient Address");
        $crud->display_as("RecipientCity", $this->lang->line('Recipient City'));
        $crud->display_as("RecipientZipCode", "Recipient Zip Code");
        $crud->display_as("RecipientPhone", "Recipient Phone");
        $crud->display_as("OrderTotalPayment", "Order Total Payment");

        $crud->set_relation('user_id', 'users', 'full_name', ['Id_Role' => settings('app_DeliveryAgentRoleId')]);

        if (!can_list(get_class($this))) {
            $crud->unset_list();
        }

        if (!can_read(get_class($this))) {
            $crud->unset_read();
        }

        if (!can_add(get_class($this))) {
            $crud->unset_add();
        }

        if (!can_edit(get_class($this))) {
            $crud->unset_edit();
        }

        if (!can_delete(get_class($this))) {
            $crud->unset_delete();
        }

        if ($userdata['Id_Role'] == settings('app_DeliveryAgentRoleId')) {
            $crud->where(['orders.user_id' => $userdata['user_id']]);
            $fields = ['OrderStatus'];
            $crud->required_fields('OrderStatus');
            $columns = [
                'OrderStatus',
                'OrderNo',
                'OrderDate',
                'RecipientFullName',
                'RecipientCity',
            ];
        }

        if ('list' == $crud->getState()) {
            $columns = array_merge(['OrderId'], $columns);
            $crud->callback_column('OrderStatus', function ($value) {
                return $this->lang->line($value);
            });
        }

        $crud->callback_read_field('RecipientPhone', function ($value, $primary_key) {
            return '<a href="tel:' . $value . '" class="btn btn-success btn-large fa fa-phone">&nbsp;' . $value . '</a>';
        });

        $crud->callback_before_update([
            $this,
            'update_RemainingQuantity_before_update',
        ]);
        $crud->callback_after_update([
            $this,
            'update_RemainingQuantity_after_update',
        ]);
        $crud->callback_after_insert([
            $this,
            'send_notification',
        ]);

        $crud->columns($columns);
        $crud->fields($fields);

        $javascript = "";

        if ('list' == $crud->getState() || 'success' == $crud->getState()) {
            $form_open = form_open('Orders/changeStatus', ['style' => 'display:inline-block;']);
            $form_close = form_close();

            $javascript = <<<EOF
	$(function() {
			$('#main-table-box form #quickSearchBox').remove();
			$('#main-table-box #filtering_form').remove();
			$('#main-table-box #flex1').css('margin-bottom', 0);
			$('#main-table-box table div[rel="OrderId"]').html("<input type=\"checkbox\" name=\"orders-all\" id=\"orders-all\" value=\"-1\">");

			$("#main-table-box #flex1 tbody tr").each(function() {
				var orderId = $(this).find('td:first-child').text().trim();

				$(this).find('td:first-child').html("<input type='checkbox' name='orders' id='order-checkbox-" + orderId + "' class='orders-checkbox' value='" + orderId + "'>")
			});

			$('#main-table-box .tDiv').prepend(`
$form_open
	<select name="status" id="orders-status" class="chosen-select">
		<option value="0" selected disabled>{$this->lang->line('Status')}</option>
		<option value="New">{$this->lang->line('New')}</option>
		<option value="Dispatched">{$this->lang->line('Dispatched')}</option>
		<option value="Delivered">{$this->lang->line('Delivered')}</option>
		<option value="Returned">{$this->lang->line('Returned')}</option>
		<option value="Cancelled">{$this->lang->line('Cancelled')}</option>
		<option value="No Response">{$this->lang->line('No Response')}</option>
	</select>
	<button title="Change Status" class="btn btn-default" type="submit" id="orders-status-btn" disabled>{$this->lang->line('Change Status')}</button>
$form_close

<div class="sDiv quickSearchBox" id="quickSearchBox" style="display: inline-block; background: none; border: none;">
    <label for="search_text" style="margin-bottom: 19px;">{$this->lang->line('Search')}:</label>

    <input type="text" class="qsbsearch_fieldox search_text" name="search_text" size="20" id="search_text">

    <select name="search_field" id="search_field" style="width: 100px;">
        <option value="all">{$this->lang->line('Search all')}</option>
        <option value="OrderId">{$this->lang->line('Order Id')}</option>
        <option value="OrderStatus">{$this->lang->line('Order Status')}</option>
        <option value="OrderNo">{$this->lang->line('Order No')}</option>
        <option value="OrderDate">{$this->lang->line('Order Date')}</option>
<!--
        <option value="Product">{$this->lang->line('Product')}</option>
-->
        <option value="RecipientFullName">{$this->lang->line('Recipient Full Name')}</option>
        <option value="RecipientCity">{$this->lang->line('Recipient City')}</option>
        <option value="DeliveryAgent">{$this->lang->line('Delivery Agent')}</option>
    </select>

	<input type="button" value="{$this->lang->line('Search')}" class="crud_search" id="search_button">

    <input type="button" value="{$this->lang->line('Clear filtering')}" id="search_clear" class="search_clear">
</div>
			`);

			$('#main-table-box').append(`
<div id="filtering_form" class="filtering_form flexigrid">
    <div class="pDiv">
        <div class="pDiv2">
            <div class="pGroup">
				<span class="pcontrol">
                    {$this->lang->line('Show')}
                    <select name="per_page" id="per_page" class="per_page">
                            <option value="10" selected="selected">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                    </select>
                    {$this->lang->line('entries')}
                    <input type="hidden" name="order_by[0]" id="hidden-sorting" class="hidden-sorting" value="OrderDate">
					<input type="hidden" name="order_by[1]" id="hidden-ordering" class="hidden-ordering" value="DESC">
				</span>
            </div>

            <div class="btnseparator"></div>

            <div class="pGroup">
                <div class="pFirst pButton first-button">
                    <span></span>
                </div>
                <div class="pPrev pButton prev-button">
                    <span></span>
                </div>
            </div>

            <div class="btnseparator"></div>

            <div class="pGroup">
				<span class="pcontrol">
                    {$this->lang->line('Page')} <input name="page" type="text" value="1" size="5" id="crud_page" class="crud_page"> of <span id="last-page-number" class="last-page-number">3</span>
                </span>
            </div>

            <div class="btnseparator"></div>

            <div class="pGroup">
                <div class="pNext pButton next-button">
                    <span></span>
                </div>
                <div class="pLast pButton last-button">
                    <span></span>
                </div>
            </div>

            <div class="btnseparator"></div>

            <div class="pGroup">
                <div class="pReload pButton ajax_refresh_and_loading" id="ajax_refresh_and_loading">
                    <span></span>
                </div>
            </div>

            <div class="btnseparator"></div>

            <div class="pGroup">
				<span class="pPageStat">{$this->lang->line('Displaying')} <span id="page-starts-from" class="page-starts-from">1</span> {$this->lang->line('to')} <span id="page-ends-to" class="page-ends-to">10</span> {$this->lang->line('of')} <span id="total_items" class="total_items">24</span> {$this->lang->line('items')}</span>
            </div>
        </div>

        <div style="clear: both;"></div>
    </div>
</div>
			`);
			$("#main-table-box #crud_page").attr('type', 'number');
			$("#main-table-box #crud_page").attr('min', 1);

			$("#main-table-box .pFirst.pButton.first-button").on('click', function () {
				$("#main-table-box #crud_page").val(1);
			});

			$("#main-table-box .pPrev.pButton.prev-button").on('click', function () {
				var crud_page = parseInt($("#main-table-box #crud_page").val());

				if (1 < crud_page) {
					$("#main-table-box #crud_page").val(parseInt($("#main-table-box #crud_page").val()) - 1);
				}
			});

			$("#main-table-box .pNext.pButton.next-button").on('click', function () {
				var crud_page = parseInt($("#main-table-box #crud_page").val());
				var last_page = parseInt($("#main-table-box #last-page-number").text());

				if (crud_page + 1 <= last_page) {
					$("#main-table-box #crud_page").val(crud_page + 1);
				}
			});

			$("#main-table-box .pLast.pButton.last-button").on('click', function () {
				$("#main-table-box #crud_page").val(parseInt($("#main-table-box #last-page-number").text()));
			});

			if (0 < $("#main-table-box .orders-checkbox:checked").length) {
				$('#main-table-box #orders-status-btn').prop('disabled', false);
			}

			$("#main-table-box #orders-all").on('change', function (event) {
		        if($("#main-table-box #orders-all:checked").length) {
            		$("#main-table-box .orders-checkbox").each(function() {
            			$(this).prop('checked', true);
            		});
            		$('#main-table-box #orders-status-btn').prop('disabled', false);
				} else {
					$("#main-table-box .orders-checkbox").each(function() {
            			$(this).prop('checked', false);
            		});
            		$('#main-table-box #orders-status-btn').prop('disabled', true);
				}
			});

		    $("#main-table-box .orders-checkbox").on('change', function() {
		        if($("#main-table-box .orders-checkbox:checked").length == $("#main-table-box .orders-checkbox").length){
		            $("#main-table-box #orders-all").prop('checked', true);
		        } else {
		            $("#main-table-box #orders-all").prop('checked', false);
		        }
		    });

			$("#main-table-box .orders-checkbox").on('change', function (event) {
				if (0 < $("#main-table-box .orders-checkbox:checked").length) {
					$('#main-table-box #orders-status-btn').prop('disabled', false);
				} else {
					$('#main-table-box #orders-status-btn').prop('disabled', true);
				}
				//orders-status-btn
			});

			$("#main-table-box .orders-checkbox").on('change', function (event) {
				if($(this).is(":checked")) {
					$('#main-table-box .tDiv form').append(`<input type="hidden" name="orders[]" id="order-input-` + $(this).val() + `" value="` + $(this).val() + `">`);
				} else {
					$('#main-table-box .tDiv form #order-input-' + $(this).val()).remove();
				}
			});

			$("#main-table-box #orders-all").on('change', function (event) {
				if($(this).is(":checked")) {
					$("#main-table-box .orders-checkbox").each(function() {
						if($("#main-table-box #order-input-" + $(this).val()).length <= 0) {
							$('#main-table-box .tDiv form').append(`<input type="hidden" name="orders[]" id="order-input-` + $(this).val() + `" value="` + $(this).val() + `">`);
						}
					});
				} else {
					$("#main-table-box .orders-checkbox").each(function() {
						if($("#main-table-box #order-input-" + $(this).val()).length) {
							$('#main-table-box .tDiv form #order-input-' + $(this).val()).remove();
						}
					});
				}
			});

			// Filter
			var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
			var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
			var data = jQuery.param({
				"search_text": search_text,
				"search_field": search_field,
				"per_page": 10,
				"order_by[0]": "OrderId",
				"order_by[1]": "asc",
				"page": 1
			});
			$.get("/orders/search?new_orders=$new_orders&" + data, search);

			$('#main-table-box .tDiv #quickSearchBox #search_field').on('change', function (event) {
				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": 10,
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": 1
				});
				$.get("/orders/search?new_orders=$new_orders&" + data, search);
			});

//			$('#main-table-box .tDiv #quickSearchBox #search_text').on('keyup', function (event) {
//				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
//				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
//				var data = jQuery.param({
//					"search_text": search_text,
//					"search_field": search_field,
//					"per_page": 10,
//					"order_by[0]": "OrderId",
//					"order_by[1]": "asc",
//					"page": 1
//				});
//				$.get("/orders/search?new_orders=$new_orders&" + data, search);
//			});

//			$('#main-table-box .tDiv #quickSearchBox #search_text').on('keyup', function (event) {
//				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
//				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
//				var data = jQuery.param({
//					"search_text": search_text,
//					"search_field": search_field,
//					"per_page": 10,
//					"order_by[0]": "OrderId",
//					"order_by[1]": "asc",
//					"page": 1
//				});
//				$.get("/orders/search?new_orders=$new_orders&" + data, search);
//			});

			$('#main-table-box .tDiv #quickSearchBox #search_button').on('click', function (event) {
				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": 10,
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": 1
				});
				$.get("/orders/search?new_orders=$new_orders&" + data, search);
			});

			$("#main-table-box .pFirst.pButton.first-button, #main-table-box .pPrev.pButton.prev-button, #main-table-box .pNext.pButton.next-button, #main-table-box .pLast.pButton.last-button").on('click', function () {
				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();

				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": $("#main-table-box #per_page").val(),
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": $("#main-table-box #crud_page").val()
				});
				$.get("/orders/search?new_orders=$new_orders&" + data, search);
			});
			$("#main-table-box #per_page").on('change', function () {
				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();

				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": $("#main-table-box #per_page").val(),
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": $("#main-table-box #crud_page").val()
				});
				$.get("/orders/search?new_orders=$new_orders&" + data, search);
			});

			$("#main-table-box #search_clear").on('click', function () {
				$("#main-table-box #search_text").val('');

				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();

				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": $("#main-table-box #per_page").val(),
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": $("#main-table-box #crud_page").val()
				});
				$.get("/orders/search?new_orders=$new_orders&" + data, search);
			});

        $("#search_text").autocomplete({
			source: function( request, response ) {
				var search_text = $('#main-table-box .tDiv #quickSearchBox #search_text').val();
				var search_field = $('#main-table-box .tDiv #quickSearchBox #search_field').val();
				var data = jQuery.param({
					"search_text": search_text,
					"search_field": search_field,
					"per_page": 10,
					"order_by[0]": "OrderId",
					"order_by[1]": "asc",
					"page": 1
				});
				$.getJSON("/orders/searchAutocomplete?" + data, request, function( data, status, xhr ) {
					response( data );
				});
			},
            minLength: 0,
        });
	});

function search(data, status) {
	var data = JSON.parse(data);
	var tbody = "";

	$("#main-table-box #total_items").text(data.total_orders);
	$("#main-table-box #page-ends-to, #main-table-box #last-page-number").text(data.total_pages);
	$("#main-table-box #crud_page").attr('max', data.total_pages);

	data.orders.forEach(function (item) {
		tbody += `
<tr>
    <td width="10%" class="">
        <div class="text-left">
        	<input type="checkbox" name="orders" id="order-checkbox-` + item.OrderId + `" class="orders-checkbox" value="` + item.OrderId + `">
        </div>
    </td>
    <td width="10%" class="">
        <div class="text-left">` + item.OrderStatus + `</div>
    </td>
    <td width="10%" class="sorted">
        <div class="text-left">` + item.OrderNo + `</div>
    </td>
    <td width="10%" class="">
        <div class="text-left">` + item.OrderDate + `</div>
    </td>
    <!--
    <td width="10%" class="">
        <div class="text-left">` + item.Name + `</div>
    </td>
    -->
    <td width="10%" class="">
        <div class="text-left">` + item.RecipientFullName + `</div>
    </td>
    <td width="10%" class="">
        <div class="text-left">` + item.RecipientCity + `</div>
    </td>
    <td width="10%" class="">
        <div class="text-left">` + item.full_name + `</div>
    </td>
    <td align="left" width="20%">
        <div class="tools flexigrid">
            <a href="/orders/index/delete/` + item.OrderId + `" title="Delete Order" class="delete-row btn btn-default"><span class="delete-icon"></span></a>
            <a href="/orders/index/edit/` + item.OrderId + `" title="Edit Order" class="edit_button btn btn-default"><span class="edit-icon"></span></a>
            <a href="/orders/index/read/` + item.OrderId + `" title="View Order" class="edit_button btn btn-default"><span class="read-icon"></span></a>

            <div class="clear"></div>
        </div>
    </td>
</tr>
		`;
	});

	$('#flex1 tbody').html(tbody);
}
EOF;
        }

        if ('read' == $crud->getState()) {
            $this->db->join('products', 'products.ProductId = order_product.ProductId');
            $this->db->where('order_product.OrderId', $crud->getStateInfo()->primary_key);

            $products = $this->db->get('order_product')->result_array();
            $productsHtml = '';

            foreach ($products as $product) {
                $productsHtml .= "
<tr class=\"form-field-box even\">
	<td>{$product['Name']}</td>
	<td>{$product['Quantity']}</td>
</tr>
				";
            }

            $javascript = <<<EOF
$(function() {
	$('#main-table-box form .form-div #ProductId_field_box').remove();
	$('#main-table-box form .form-div #Quantity_field_box').remove();

		$('#main-table-box form .form-div #OrderTotalPayment_field_box').after(`
<div class="form-field-box odd">
	<hr>

	<div class="title">
		<h3>Order details</h3>
	</div>

	<hr>

	<table>
		<thead>
			<tr>
				<td>Product Name</td>
				<td>Quantity</td>
			</tr>
		</thead>
		<tbody id="order-details">
			{$productsHtml}
		</tbody>
	</table>

	<hr>
</div>
	`);
});
EOF;
        }

        if ('add' == $crud->getState() || 'edit' == $crud->getState()) {
            $products = $this->db->get('products')->result_array();

            $orderDetailsHtml = 'let orderDetails = [';
            if ('edit' == $crud->getState()) {
                $this->db->join('products', 'products.ProductId = order_product.ProductId');
                $this->db->where('order_product.OrderId', $crud->getStateInfo()->primary_key);

                $orderDetails = $this->db->get('order_product')->result_array();

                foreach ($orderDetails as $item) {
                    $orderDetailsHtml .= "{\"product\":\"{$item['ProductId']}\",\"quantity\":\"{$item['Quantity']}\",},";
                }
            }
            $orderDetailsHtml .= '];';

            $productsHtml = '';
            foreach ($products as $product) {
                $productsHtml .= "<option value=\"{$product['ProductId']}\">{$product['Name']}</option>";
            }

            $javascript = <<<EOF
{$orderDetailsHtml}
let productStub = `
<div class="form-field-box even">
	<div class="form-display-as-box">Product :</div>

	<div class="form-input-box">
		<select name="products[]" class="form-control" style="width:200px;display:inline-block;">
			<option value="0" selected disabled>Select a product</option>
			{$productsHtml}
		</select>

		<input type="number" name="quantity[]" min="1"	class="form-control" style="width:90px;display:inline-block;margin-left:5px;" placeholder="Qty">

		<button type="button" style="display:inline-block;margin-left:5px;" onclick="removeLine(this)">X</button>
	</div>

	<div class="clear"></div>
</div>
`;

$(function() {
	$('#form-button-save').remove();
	$('#main-table-box form .form-div #OrderTotalPayment_field_box').after(`
<div class="form-field-box odd">
	<hr>

	<div class="title">
		<h3 style="display:inline-block;width:70%;">Order details</h3>

		<button type="button" style="display:inline-block;" onclick="addNewLine()">Add new line</button>

		<div class="clear"></div>
	</div>

	<hr>

	<div id="order-details">
		` + productStub + `
	</div>

	<hr>
</div>
	`);

	$("select[name='product[]']").chosen();

	$('#main-table-box .form-div #order-details').html('');

	orderDetails.forEach(function (item) {
		addNewLine();

		$('#main-table-box #order-details select[name="products[]"]:last').val(item.product).trigger("chosen:updated");
		$('#main-table-box #order-details input[name="quantity[]"]:last').val(item.quantity);
	});
});


function addNewLine() {
	$('#main-table-box .form-div #order-details').append(productStub);
	$("select[name='product[]']").chosen();
}

function removeLine(element) {
	$(element).parent().parent().remove();
}
EOF;
        }

        $this->template->write('title', $this->lang->line('Customers Orders'), true);
        $this->template->write('header', $this->lang->line('Customers Orders'));
        $this->template->write_view('content', 'example', $crud->render());
        $this->template->write('style', "");
        $this->template->write('javascript', $javascript);
        $this->template->render();
    }

    public function searchAutocomplete()
    {
        $search_text = $this->input->get('search_text');
        $search_field = $this->input->get('search_field');

        $output = [];

        if ('RecipientCity' == $search_field) {
            $sql = "SELECT DISTINCT RecipientCity FROM `orders` o WHERE o.RecipientCity LIKE \"$search_text%\"";
            $query = $this->db->query($sql);
            $result = $query->result_array();

            foreach ($result as $city) {
                $output[] = [
                    'id'    => $city['RecipientCity'],
                    'value' => $city['RecipientCity'],
                ];
            }
        }

        echo json_encode($output);
        return;
    }

    public function search()
    {
        $status = $this->input->get('new_orders');
        $search_text = $this->input->get('search_text');
        $search_field = $this->input->get('search_field');
        $per_page = $this->input->get('per_page');
        $page = $this->input->get('page');
        $offset = ($page - 1) * $per_page;
        $roleId = settings('app_DeliveryAgentRoleId');
        $sql = <<<EOF
SELECT *
FROM `orders` o
# JOIN products p ON p.ProductId = o.ProductId
JOIN users u ON u.user_id = o.user_id
WHERE u.Id_Role = {$roleId}

EOF;

        if ('new' == $status) {
            $sql .= "AND o.OrderStatus = \"New\"\n";
        }

        if ('all' == $search_field) {
            $sql .= <<<EOF
AND (
	o.OrderStatus LIKE "{$search_text}%"
	OR o.OrderNo LIKE "{$search_text}%"
	OR o.OrderDate LIKE "{$search_text}%"
# 	OR p.Name LIKE "{$search_text}%"
	OR o.RecipientFullName LIKE "{$search_text}%"
	OR o.RecipientCity LIKE "{$search_text}%"
	OR u.full_name LIKE "{$search_text}%"
)
EOF;
        } else {
            $sql .= <<<EOF
AND $search_field LIKE "{$search_text}%"
EOF;
        }

        $query = $this->db->query($sql);
        $orders = $query->result_array();

        $total_orders = count($orders);
        $total_pages = ceil($total_orders / $per_page);

        $sql .= <<<EOF

ORDER BY o.OrderDate DESC
LIMIT {$per_page}
OFFSET {$offset}
EOF;

        $query = $this->db->query($sql);
        $orders = $query->result_array();

        echo json_encode([
            'total_orders' => $total_orders,
            'total_pages'  => $total_pages,
            'orders'       => $orders,
        ]);
        return;
    }

    public function import($all = null)
    {
        try {
            $now = date("c");
            $shop = settings('app_ShopifyStore');
            $key = settings('app_ShopifyAPIKey');
            $pwd = settings('app_ShopifyAPIPassword');

            if (isset($all)) {
                $this->db->empty_table('orders');
            } else {
                $updated_at_min = file_exists("updated_at_min") && !isset($all) ?
                file_get_contents("updated_at_min") : null;
            }

            $url = "https://$key:$pwd@$shop/admin/api/2019-10/orders.json?";
            $url .= isset($updated_at_min) ? "updated_at_min=$updated_at_min&" : "";
            $url .= "status=open&fulfillment_status=unfulfilled&";
            $url .= "fields=id,created_at,order_number,total_price,tags,line_items,shipping_address&";
            $url .= "limit=250";

            $result = file_get_contents($url);
            $orders = json_decode($result, true)["orders"];

            // Getting confirmed orders only
            $this->session->set_userdata('orders_filter', Settings("app_ShopifyOrdersTagFilter"));
            $confirmed = array_filter($orders, function ($o) {
                return in_array($this->session->orders_filter, explode(",", $o["tags"]));
            });

            $added = 0;
            foreach ($confirmed as $order) {
                $curr_order = [
                    'OrderId'           => $order["id"],
                    'user_id'           => "",
                    'OrderNo'           => $order["order_number"],
                    'OrderStatus'       => "New",
                    'OrderDate'         => $order["created_at"],
                    'RecipientFullName' => $order["shipping_address"]["first_name"] . " " . $order["shipping_address"]["last_name"],
                    'RecipientAddress'  => $order["shipping_address"]["address1"],
                    'RecipientCity'     => $order["shipping_address"]["city"],
                    'RecipientZipCode'  => 0,
                    'RecipientPhone'    => $order["shipping_address"]["phone"],
                    'OrderTotalPayment' => $order["total_price"],
                ];

                $this->db->insert('orders', $curr_order);
                $order_id = $this->db->insert_id();

                $curr_products = [];
                foreach ($order["line_items"] as $line_item) {
                    $curr_products[] = [
                        'OrderId'   => $order_id,
                        'ProductId' => $line_item["product_id"],
                        'Quantity'  => $line_item["quantity"],
                    ];
                }
                $this->db->insert_batch('order_product', $curr_products);

                $added++;
            }

            file_put_contents("updated_at_min", $now);
            echo "Orders: $added added.\n";
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function update_RemainingQuantity_before_update($post_array, $primary_key)
    {
        $this->db->where('OrderId', $primary_key);
        $order = $this->db->get('orders')->row();

        $_SESSION['status'] = $order->OrderStatus;
        //$_SESSION['status'] = $post_array['OrderStatus'];
    }

    public function update_RemainingQuantity_after_update($post_array, $primary_key)
    {
        if ($post_array['OrderStatus'] == 'New') {
            $this->send_notification($post_array, $primary_key);
        }

        $status = $_SESSION['status'];
        unset($_SESSION['status']);

        $user_id = $post_array['user_id'];

        $sql = "SELECT *
                FROM `shipments` shp
                WHERE shp.`user_id` = $user_id
                AND RemainingQuantity >= 0
                ORDER BY ShipmentDate ASC
                LIMIT 2";

        $query = $this->db->query($sql);
        $data = $query->result_array();

        if ($post_array['OrderStatus'] == 'Delivered') {

            if ($data[0]["RemainingQuantity"] >= $post_array['Quantity']) {

                $variable = $data[0]["RemainingQuantity"] - $post_array['Quantity'];
                $this->db->set('RemainingQuantity', $variable);
                $this->db->where('ProductId', $data[0]["ProductId"]);
                $this->db->where('user_id', $data[0]["user_id"]);
                $this->db->where('ShipmentDate', $data[0]["ShipmentDate"]);
                $this->db->update('shipments');
            } elseif (count($data) > 1 && $data[0]["RemainingQuantity"] == 0) {
                $variable = $data[1]["RemainingQuantity"] - $post_array['Quantity'];
                $this->db->set('RemainingQuantity', $variable);
                $this->db->where('ProductId', $data[1]["ProductId"]);
                $this->db->where('user_id', $data[1]["user_id"]);
                $this->db->where('ShipmentDate', $data[1]["ShipmentDate"]);
                $this->db->update('shipments');

            } elseif (count($data) > 1 && $data[0]["RemainingQuantity"] == 1 && $data[1]["RemainingQuantity"] >= $post_array['Quantity'] + 1) {
                $variable = $data[0]["RemainingQuantity"] - 1;
                $this->db->set('RemainingQuantity', $variable);
                $this->db->where('ProductId', $data[0]["ProductId"]);
                $this->db->where('user_id', $data[0]["user_id"]);
                $this->db->where('ShipmentDate', $data[0]["ShipmentDate"]);
                $this->db->update('shipments');

                $variable2 = $data[1]["RemainingQuantity"] - $post_array['Quantity'] + 1;
                $this->db->set('RemainingQuantity', $variable2);
                $this->db->where('ProductId', $data[1]["ProductId"]);
                $this->db->where('user_id', $data[1]["user_id"]);
                $this->db->where('ShipmentDate', $data[1]["ShipmentDate"]);
                $this->db->update('shipments');
            } elseif ($data[0]["RemainingQuantity"] < $post_array['Quantity'] && count($data) > 1) {

                $this->db->set('RemainingQuantity', 0);
                $this->db->where('ProductId', $data[0]["ProductId"]);
                $this->db->where('user_id', $data[0]["user_id"]);
                $this->db->where('ShipmentDate', $data[0]["ShipmentDate"]);
                $this->db->update('shipments');

                $variable2 = $data[1]["RemainingQuantity"] - $post_array['Quantity'] + $data[0]["RemainingQuantity"];
                $this->db->set('RemainingQuantity', $variable2);
                $this->db->where('ProductId', $data[1]["ProductId"]);
                $this->db->where('user_id', $data[1]["user_id"]);
                $this->db->where('ShipmentDate', $data[1]["ShipmentDate"]);
                $this->db->update('shipments');
            }
        } elseif ($post_array['OrderStatus'] == 'Returned' && $status == 'Delivered') {

            $variable = $data[0]["RemainingQuantity"] + $post_array['Quantity'];
            $this->db->set('RemainingQuantity', $variable);
            $this->db->where('ProductId', $data[0]["ProductId"]);
            $this->db->where('user_id', $data[0]["user_id"]);
            $this->db->where('ShipmentDate', $data[0]["ShipmentDate"]);
            $this->db->update('shipments');
        }
    }

    public function changeStatus()
    {
        $status = $this->input->post('status');
        $orders = $this->input->post('orders');
        $this->load->helper('url');

        if (!in_array($status, [
            'New',
            'Dispatched',
            'Delivered',
            'Returned',
            'Cancelled',
            'No Response',
        ]) || empty($orders)) {
            $this->session->set_flashdata('error', 'Orders status not changed');

            redirect('/orders');
            return;
        }

        $this->db->where_in('OrderId', $orders);
        $this->db->update('orders', ['OrderStatus' => $status]);

        $this->session->set_flashdata('success', 'Orders status changed successfully');

        redirect('/orders');
        return;
    }

    public function send_notification($post_array, $primary_key)
    {
        if (0 < count($post_array['products'])) {
            $this->db->delete('order_product', ['OrderId' => $primary_key]);

            for ($i = 0; $i < count($post_array['products']); $i++) {
                $data = [
                    'OrderId'   => $primary_key,
                    'ProductId' => $post_array['products'][$i],
                    'Quantity'  => $post_array['quantity'][$i],
                ];

                $this->db->insert('order_product', $data);
            }
        }

        $order = $this->db
            ->select('orders.OrderId, orders.OrderNo, orders.Quantity, products.Name')
            ->from('orders')
            ->join('products', 'products.ProductId = orders.ProductId')
            ->where('orders.OrderId', $primary_key)
            ->get()
            ->row_array();

        // array of notifications
        $notifications = $this->db
            ->where('user_id', $post_array['user_id'])
            ->get('notifications')
            ->result_array();

        foreach ($notifications as $notification) {
            /*
            const options = {
            "//": "Visual Options",
            "body": "<String>",
            "icon": "<URL String>",
            "image": "<URL String>",
            "badge": "<URL String>",
            "vibrate": "<Array of Integers>",
            "sound": "<URL String>",
            "dir": "<String of 'auto' | 'ltr' | 'rtl'>",
            "//": "Behavioural Options",
            "tag": "<String>",
            "data": "<Anything>",
            "requireInteraction": "<boolean>",
            "renotify": "<Boolean>",
            "silent": "<Boolean>",
            "//": "Both Visual & Behavioural Options",
            "actions": "<Array of Strings>",
            "//": "Information Option. No visual affect.",
            "timestamp": "<Long>"
            }
             */
            $data = [
                'user_id'         => $post_array['user_id'],
                'notification_id' => $notification['id'],
                'payload'         => json_encode([
                    'title'   => "Order #{$order['OrderNo']} assigned.",
                    'link'    => base_url() . "/orders/index/read/{$order['OrderId']}",
                    'options' => [
                        'body'  => "{$order['Quantity']} x {$order['Name']}",
                        'icon'  => "asset/images/icon.png",
                        'badge' => "asset/images/badge.png",
                    ],
                ]),
            ];

            $res = $this->db->insert('job_queue', $data);
        }
    }
}
