<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Controller
 */
class Dashboard extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		
		$this->load->helper('general_helper');
		
		if (!is_user_logged_in()) {
			redirect('login');
		}
		$userdata = $this->session->userdata();
		if ($userdata['Id_Role'] == 2 && base_url('/')) {
			redirect('orders');
		}
		
		$this->load->database();
		$this->load->helper('url');
		$this->load->library('grocery_CRUD');
		
		$this->template->write_view('sidenavs', 'template/default_sidenavs', true);
		$this->template->write_view('navs', 'template/default_topnavs.php', true);
	}
	
	function index()
	{
		$today                    = new DateTime();
		$output['deliveryAgents'] = $this->db
			->query("
SELECT u.user_id, r.Id_Role, u.full_name, (COALESCE(query2.TOTAL_DELIVERED / query1.TOTAL, 0) * 100) AS percentage_delivered, COALESCE(query2.TOTAL_DELIVERED, 0) AS total_delivered, COALESCE(query1.TOTAL, 0) AS total
FROM users u
LEFT JOIN roles r ON r.Id_Role = u.Id_Role
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL
	FROM orders o
	WHERE MONTH(o.OrderDate) = {$today->format('m')}
	AND YEAR(o.OrderDate) = {$today->format('Y')}
	GROUP BY o.user_id
) query1 ON query1.user_id = u.user_id
LEFT JOIN (
	SELECT o.user_id, COUNT(*) AS TOTAL_DELIVERED
	FROM orders o
	WHERE o.OrderStatus = 'Delivered'
	AND MONTH(o.OrderDate) = {$today->format('m')}
	AND YEAR(o.OrderDate) = {$today->format('Y')}
	GROUP BY o.user_id, o.OrderStatus
) query2 ON query2.user_id = u.user_id
WHERE r.Id_Role = 2
GROUP BY u.user_id
ORDER BY percentage_delivered DESC
LIMIT 5;
")
			->result_array();
		
		$total            = $this->db
			->query("
SELECT COUNT(*) AS TOTAL
FROM orders o
WHERE MONTH(o.OrderDate) = MONTH(CURRENT_TIMESTAMP)
AND YEAR(o.OrderDate) = YEAR(CURRENT_TIMESTAMP)
GROUP BY o.ProductId;
				")
			->row_array();
		$topProductsTotal = $total ? $total['TOTAL'] : 0;
		
		$topProducts = $this->db
			->query("
    SELECT p.Name, o.RecipientCity AS city, COUNT(*) AS total_city
	FROM orders o
	JOIN products p ON p.ProductId = o.ProductId
	WHERE o.OrderStatus = 'Delivered'
	AND MONTH(o.OrderDate) = MONTH(CURRENT_TIMESTAMP)
	AND YEAR(o.OrderDate) = YEAR(CURRENT_TIMESTAMP)
	GROUP BY o.RecipientCity, o.ProductId
	ORDER BY total_city, city DESC;
	")
			->result_array();
		
		$output['topProductsCities'] = [];
		foreach ($topProducts as $topProduct) {
			if (isset($output['topProductsCities'][$topProduct['city']])) {
				continue;
			}
			
			$output['topProductsCities'][$topProduct['city']][] = [
				'name'       => $topProduct['Name'],
				'total'      => $topProduct['total_city'],
				'percentage' => round(($topProduct['total_city'] / $topProductsTotal) * 100),
			];
		}
		
		$output['newOrders'] = $this->db
			->query("
SELECT o.OrderNo, o.OrderId, p.Name, o.Quantity, o.RecipientFullName, o.RecipientCity
FROM orders o
JOIN products p ON p.ProductId = o.ProductId
WHERE o.user_id IS NULL
OR o.user_id = 0
AND o.OrderStatus = 'New';
")
			->result_array();
		
		$this->template->write('title', 'Dashboard', true);
		$this->template->write('header', 'Dashboard');
		$this->template->write('content', $this->load->view('tes/dashboard', $output, true));
		$this->template->write('style', "");
		$this->template->render();
	}
}
