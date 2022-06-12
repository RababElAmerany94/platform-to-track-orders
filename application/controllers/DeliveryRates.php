<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery Rates Controller
 */
class DeliveryRates extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper('general_helper');
		$this->load->database();
		
		if (!is_user_logged_in()) {
			redirect('login');
		}
		$userdata = $this->session->userdata();
		if ($userdata['Id_Role'] == 2 && base_url('/')) {
			redirect('orders');
		}
		
		$this->template->write_view('sidenavs', 'template/default_sidenavs', true);
		$this->template->write_view('navs', 'template/default_topnavs.php', true);
	}
	
	function index()
	{
		$output = [];
		
		$javascript = <<<EOF
$('#month-select').on('change', function () {
	filling_table();
});

$('#year-select').on('change', function () {
	filling_table();
});

function filling_table() {
    var month = $("#month-select").val();
    var year = $("#year-select").val();

    if (month && month.length > 0 && year && year.length > 0) {
        $.ajax({
            beforeSend: function () {
                // before send request, hide the dropdown and display loading
                $(".loading").show();
                $("#delivery-rates").hide();
                $("#delivery-rates-table tbody").html('');
            },
            url: "get_delivery_rates?month=" + month + "&year=" + year,
            success: function (response) {
                response = JSON.parse(response);
                
                // update UI
                response.forEach(function (item, index) {
                    tr = $('<tr></tr>');
                    tr.append('<td>' + item.full_name + '</td>');
                    tr.append('<td>' + Math.floor(item.percentage_delivered) + '% (' + item.total_delivered + ')</td>');
                    tr.append('<td>' + Math.floor(item.percentage_no_response) + '% (' + item.total_no_response + ')</td>');
                    tr.append('<td>' + Math.floor(item.percentage_cancelled) + '% (' + item.total_cancelled + ')</td>');
                    tr.append('<td>' + Math.floor(item.percentage_returned) + '% (' + item.total_returned + ')</td>');

                    $("#delivery-rates-table tbody").append(tr);
                });

                $("#loading").hide();
                $("#delivery-rates").show();
                $("#generate_report").removeAttr("disabled");
            }
        });
    }
}
EOF;
		$this->template->write('title', 'Delivery Rates per Month per DA', true);
		$this->template->write('header', 'Delivery Rates per Month per DA');
		$this->template->write('content', $this->load->view('tes/delivery_rates', $output, true));
		$this->template->write('style', "");
		$this->template->write('javascript', $javascript);
		$this->template->render();
	}
	
	function delivery_rates($month, $year)
	{
		return $this->db
			->query("
SELECT u.user_id, u.full_name, r.Id_Role,
	(COALESCE(query1.TOTAL_DELIVERED / query0.TOTAL, 0) * 100) AS percentage_delivered, COALESCE(query1.TOTAL_DELIVERED, 0) AS total_delivered,
	(COALESCE(query2.TOTAL_CANCELLED / query0.TOTAL, 0) * 100) AS percentage_cancelled, COALESCE(query2.TOTAL_CANCELLED, 0) AS total_cancelled,
	(COALESCE(query3.TOTAL_RETURNED / query0.TOTAL, 0) * 100) AS percentage_returned, COALESCE(query3.TOTAL_RETURNED, 0) AS total_returned,
	(COALESCE(query4.TOTAL_NO_RESPONSE / query0.TOTAL, 0) * 100) AS percentage_no_response, COALESCE(query4.TOTAL_NO_RESPONSE, 0) AS total_no_response,
	 COALESCE(query0.TOTAL, 0) AS total
FROM users u
LEFT JOIN roles r ON r.Id_Role = u.Id_Role
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL
	FROM orders o
	WHERE YEAR(o.OrderDate) = $year
	AND MONTH(o.OrderDate) = $month
	GROUP BY o.user_id
) query0 ON query0.user_id = u.user_id
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL_DELIVERED
	FROM orders o
	WHERE o.OrderStatus = 'Delivered'
	AND YEAR(o.OrderDate) = $year
	AND MONTH(o.OrderDate) = $month
	GROUP BY o.user_id, o.OrderStatus
) query1 ON query1.user_id = u.user_id
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL_CANCELLED
	FROM orders o
	WHERE o.OrderStatus = 'Cancelled'
	AND YEAR(o.OrderDate) = $year
	AND MONTH(o.OrderDate) = $month
	GROUP BY o.user_id, o.OrderStatus
) query2 ON query2.user_id = u.user_id
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL_RETURNED
	FROM orders o
	WHERE o.OrderStatus = 'Returned'
	AND YEAR(o.OrderDate) = $year
	AND MONTH(o.OrderDate) = $month
	GROUP BY o.user_id, o.OrderStatus
) query3 ON query3.user_id = u.user_id
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL_NO_RESPONSE
	FROM orders o
	WHERE o.OrderStatus = 'No Response'
	AND YEAR(o.OrderDate) = $year
	AND MONTH(o.OrderDate) = $month
	GROUP BY o.user_id, o.OrderStatus
) query4 ON query4.user_id = u.user_id
WHERE r.Id_Role = 2
GROUP BY u.user_id;
")
			->result_array();
	}
	
	function get_delivery_rates()
	{
		$month = $this->input->get('month');
		$year = $this->input->get('year');
		
		echo json_encode($this->delivery_rates($month, $year));
		return;
	}
	
	function generate_report()
	{
		$month = $this->input->post('month');
		$year = $this->input->post('year');
		
		$data = ['data' => $this->delivery_rates($month, $year)];
		
		$filename = "DELIVERY_RATES_" . (new DateTime())->format("dmY_Hi") . '_' . (new DateTime())->getTimestamp() . '.pdf';
		$pdf_view = $this->load->view('pdf/delivery_rates', $data, true);
		
		// load the library Html2pdf
		$this->load->library('Html2pdf');
		//Set folder to save PDF to
		$this->html2pdf->folder('./assets/pdfs/');
		//Set the paper defaults
		$this->html2pdf->paper('a4', 'portrait');
		//Set the filename to save/download as
		$this->html2pdf->filename($filename);
		//Load html view
		$this->html2pdf->html($pdf_view);
		$this->html2pdf->isHtml5ParserEnabled = true;
		//Download the file
		$this->html2pdf->create('download');
		
		die('Generation Finished.');
	}
}
