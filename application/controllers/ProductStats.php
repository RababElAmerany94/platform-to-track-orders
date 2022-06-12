<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery Rates Controller
 */
class ProductStats extends CI_Controller
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
		$output['products'] = $this->db->get('products')->result_array();
		
		$javascript = <<<EOF
$('#product-select').on('change', function () {
	filling_table();
});

$('#month-select').on('change', function () {
	filling_table();
});

$('#year-select').on('change', function () {
	filling_table();
});

function filling_table() {
    var product = $("#product-select").val();
    var month = $("#month-select").val();
    var year = $("#year-select").val();

    if (product && product.length > 0 && month && month.length > 0 && year && year.length > 0) {
        $.ajax({
            beforeSend: function () {
                // before send request, hide the dropdown and display loading
                $(".loading").show();
                $("#product-stats").hide();
                $("#product-stats-table tbody").html('');
            },
            url: "get_product_stats?product=" + product + "&month=" + month + "&year=" + year,
            success: function (response) {
                response = JSON.parse(response);
                
                // update UI
                response.forEach(function (item, index) {
                    tr = $('<tr></tr>');
                    tr.append('<td>' + item.city + '</td>');
                    tr.append('<td>' + item.percentage_delivered + '% (' + item.total_delivered + ')</td>');
                    tr.append('<td>' + item.percentage_no_response + '% (' + item.total_no_response + ')</td>');
                    tr.append('<td>' + item.percentage_cancelled + '% (' + item.total_cancelled + ')</td>');
                    tr.append('<td>' + item.percentage_returned + '% (' + item.total_returned + ')</td>');

                    $("#product-stats-table tbody").append(tr);
                });

                $("#loading").hide();
                $("#product-stats").show();
                $("#generate_report").removeAttr("disabled");
            }
        });
    }
}
EOF;
		$this->template->write('title', 'Product Stats', true);
		$this->template->write('header', 'Product Stats');
		$this->template->write('content', $this->load->view('tes/product_stats', $output, true));
		$this->template->write('style', "");
		$this->template->write('javascript', $javascript);
		$this->template->render();
	}
	
	function product_stats($product, $month, $year)
	{
		$total = $this->db
			->query("
SELECT COUNT(*) AS TOTAL
FROM orders o
WHERE o.ProductId = $product
AND MONTH(o.OrderDate) = $month
AND YEAR(o.OrderDate) = $year
GROUP BY o.ProductId;
				")
			->row_array();
		$total = $total ? $total['TOTAL'] : 0;
		
		$result = $this->db
			->query("
    SELECT o.RecipientCity AS CITY, COUNT(*) AS TOTAL, 'DELIVERED' AS TYPE
	FROM orders o
	WHERE o.OrderStatus = 'Delivered'
	AND o.ProductId = $product
	AND MONTH(o.OrderDate) = $month
	AND YEAR(o.OrderDate) = $year
	GROUP BY o.RecipientCity
UNION
	SELECT o.RecipientCity AS CITY, COUNT(*) AS TOTAL, 'CANCELLED' AS TYPE
	FROM orders o
	WHERE o.OrderStatus = 'Cancelled'
	AND o.ProductId = $product
	AND MONTH(o.OrderDate) = $month
	AND YEAR(o.OrderDate) = $year
	GROUP BY o.RecipientCity
UNION
    SELECT o.RecipientCity AS CITY, COUNT(*) AS TOTAL, 'RETURNED' AS TYPE
	FROM orders o
	WHERE o.OrderStatus = 'Returned'
	AND o.ProductId = $product
	AND MONTH(o.OrderDate) = $month
	AND YEAR(o.OrderDate) = $year
	GROUP BY o.RecipientCity
UNION
	SELECT o.RecipientCity AS CITY, COUNT(*) AS TOTAL, 'NO_RESPONSE' AS TYPE
	FROM orders o
	WHERE o.OrderStatus = 'No Response'
	AND o.ProductId = $product
	AND MONTH(o.OrderDate) = $month
	AND YEAR(o.OrderDate) = $year
	GROUP BY o.RecipientCity;
				")
			->result_array();
		
		$stats = [];
		if ($total) {
			foreach ($result as $item) {
				if (!isset($stats[$item['CITY']])) {
					$stats[$item['CITY']]['total_delivered'] = 0;
					$stats[$item['CITY']]['percentage_delivered'] = 0;
					$stats[$item['CITY']]['total_cancelled'] = 0;
					$stats[$item['CITY']]['percentage_cancelled'] = 0;
					$stats[$item['CITY']]['total_returned'] = 0;
					$stats[$item['CITY']]['percentage_returned'] = 0;
					$stats[$item['CITY']]['total_no_response'] = 0;
					$stats[$item['CITY']]['percentage_no_response'] = 0;
				}
				
				$stats[$item['CITY']]['city'] = ucwords($item['CITY']);
				$stats[$item['CITY']]['total_' . strtolower($item['TYPE'])] = $item['TOTAL'];
				$stats[$item['CITY']]['percentage_' . strtolower($item['TYPE'])] = round(($item['TOTAL'] / $total) * 100);
			}
		}
		
		return array_values($stats);
	}
	
	function get_product_stats()
	{
		$product = $this->input->get('product');
		$month = $this->input->get('month');
		$year = $this->input->get('year');
		
		echo json_encode($this->product_stats($product, $month, $year));
		return;
	}
	
	function generate_report()
	{
		$product = $this->input->post('product');
		$month = $this->input->post('month');
		$year = $this->input->post('year');
		
		$data = ['data' => $this->product_stats($product, $month, $year)];
		
		$filename = "PRODUCT_STATS_" . (new DateTime())->format("dmY_Hi") . '_' . (new DateTime())->getTimestamp() . '.pdf';
		$pdf_view = $this->load->view('pdf/product_stats', $data, true);
		
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
