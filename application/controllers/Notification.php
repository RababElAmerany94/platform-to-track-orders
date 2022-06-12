<?php defined('BASEPATH') OR exit('No direct script access allowed');

require __DIR__ . '/../../vendor/autoload.php';

use Minishlink\WebPush\WebPush;

/*
|   Browser                    |    URL Structure                                                                      |
|   -------                    |   -------------                                                                       |
|   Google Chrome non-VAPID    |   https://android.googleapis.com/gcm/send/{registrationId}                            |
|   Google Chrome VAPID        |   https://fcm.googleapis.com/fcm/send/{registrationId}                                |
|   Mozilla Firefox non-VAPID  |   https://updates.push.services.mozilla.com/wpush/{protocol_type}/{registrationId}    |
|   Mozilla Firefox VAPID      |   https://updates.push.services.mozilla.com/wpush/{protocol_type}/{registrationId}    |
*/

class Notification extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		if ($this->input->is_cli_request()) {
			echo "this function not accessible from the command line";
			return;
		}

		$this->load->helper('general_helper');

		if (!is_user_logged_in()) {
			redirect('login');
		}

		$subscription = json_decode(file_get_contents('php://input'), true);
		$method       = $subscription['_METHOD'];
		unset($subscription['_METHOD']);
//			$method = $_SERVER['REQUEST_METHOD'];
//			highlight_string(var_export($method, true));
//			highlight_string(var_export($subscription, true));
//			die('here');

		if (!isset($subscription['endpoint'])) {
			echo 'Error: not a subscription';
			return;
		}

		$userdata                = $this->session->userdata();
		$subscription['user_id'] = $userdata['user_id'];

		switch ($method) {
			case 'POST':
				// create a new subscription entry in your database (endpoint is unique)
				$this->db->where('user_id', $subscription['user_id']);
				$this->db->where('endpoint', $subscription['endpoint']);
				$q = $this->db->get('notifications');

				if ($q->num_rows() == 0) {
					$this->db->insert('notifications', $subscription);
				}

				break;

			case 'PUT':
				// update the key and token of subscription corresponding to the endpoint
				$this->db->where('user_id', $subscription['user_id']);
				$this->db->where('endpoint', $subscription['endpoint']);
				$q = $this->db->get('notifications');

				if ($q->num_rows() > 0) {
					$this->db->where('user_id', $subscription['user_id']);
					$this->db->where('endpoint', $subscription['endpoint']);
					$this->db->update('notifications', $subscription);
				} else {
					$this->db->insert('notifications', $subscription);
				}

				break;

			case 'DELETE':
				// delete the subscription corresponding to the endpoint
				$this->db->where('endpoint', $subscription['endpoint']);
				$this->db->delete('notifications');

				break;

			default:
				echo "Error: method not handled";

				return;
		}
	}

	function send()
	{
		echo "=======================<br>";
		echo 'Start pushing notification ' . (new DateTime())->format('d/m/Y H:i:s') . "<br>";
		echo "=======================<br><br>";

//		if (!$this->input->is_cli_request()) {
//			echo "this function accessible only from the command line";
//			return;
//		}

		// here I'll get the subscription endpoint in the POST parameters but in reality, you'll get this information in your database because you already
		// stored it (cf. push_subscription.php)


		$jobs = $this->db
			->from('job_queue')
			->get()
			->result_array();

		$auth = [
			'VAPID' => [
				'subject'    => 'mailto: my-email@some-url.com',
				'publicKey'  => 'BJ4VgIMZnNqEHG6auo8xTlvPkdp8NymRmORcvfujMZZPZITwg69LlOZNchp00HdPhaf8JLoYzec1mNbt-gyWHj8',
				'privateKey' => 'KfWIAvxZ_egTRqdKaGxHzzIayGKSkurnuGoPJvhYl04', // in the real world, this would be in a secret file
			],
		];

		$webPush = new WebPush($auth);

		echo "Fetching notification endpoints <br>";
		echo 'Total jobs count fetched : ' . count($jobs) . "<br>";
		foreach ($jobs as $job) {
			echo "<br>-----------------------<br>";
			echo "User ID : {$job['user_id']}<br>";
			echo "-----------------------<br>";
			echo 'Total jobs count fetched : ' . count($jobs) . "<br>";
			echo "-----------------------<br>";

			// array of notifications
			$notifications = $this->db
				->where('user_id', $job['user_id'])
				->get('notifications')
				->result_array();


			$jobIds = [];

			foreach ($notifications as $notification) {
				echo "Prepare Orders ID : {$job['id']}<br>";

				$sent = $webPush->sendNotification(
					$notification['endpoint'],
					$job['payload'], // optional (defaults null)
					$notification['p256dh'], // optional (defaults null)
					$notification['auth'] // optional (defaults null)
				);

				$result = $webPush->flush();

				echo "<br>=======================<br>";

				echo 'Push prepared notification result : ' . highlight_string(var_export($result, true), true) . "<br>";

				if (is_array($result)) {
					foreach ($result as $item) {
						if ($item['success'] === false || $item['expired'] === true) {
							$this->db
								->where('endpoint', $item['endpoint'])
								->delete('notifications');
						}
					}
				} else {
					$jobIds[] = $job['id'];
				}
			}

			if (!empty($jobIds)) {
				$this->db
					->where_in('id', $jobIds)
					->delete('job_queue');
			}
		}

		echo "<br>=======================<br>";
		echo 'End pushing notification ' . (new DateTime())->format('d/m/Y H:i:s') . "<br>";
		echo "=======================<br><br>";

		return;
	}
}
