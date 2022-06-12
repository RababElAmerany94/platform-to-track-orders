<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {
    function __construct() {
        parent::__construct();
        $this->load->helper('general_helper');
        $this->load->database();

        if(PHP_SAPI != 'cli' && $_SERVER['REQUEST_URI'] != "/products/import") {
            $this->load->library('grocery_CRUD');
            $this->template->write_view('sidenavs', 'template/default_sidenavs', true);
            $this->template->write_view('navs', 'template/default_topnavs.php', true);
        }
    }

    function index() {
        $this->template->write('title', 'Products', TRUE);
        $this->template->write('header', 'Products');

        $crud = new grocery_CRUD();
        $crud->set_table('products');
        $crud->set_subject('Product');

        $columns = ['ProductId','Name'];
        $fields = ['Name'];

        if ('read' == $crud->getState()) {
            $columns = ['ProductId','Name'];
        } elseif ('edit' == $crud->getState() || 'update' == $crud->getState()) {
            $fields = ['Name'];
        }
        
        $crud->display_as("ProductId","Product Id");
		$crud->display_as("Name","Name");        

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

    function import() {
        try {
            $now = date("c");
            $shop = settings('app_ShopifyStore');
            $key = settings('app_ShopifyAPIKey');
            $pwd = settings('app_ShopifyAPIPassword');
            $updated_at_min = file_exists("updated_at_min")?file_get_contents("updated_at_min"):null;

            $url = "https://$key:$pwd@$shop/admin/api/2019-10/products.json?";
            $url .= $updated_at_min?"updated_at_min=$updated_at_min&":"";
            $url .= "published_status=published&";
            $url .= "fields=id,title";

            $result = file_get_contents($url);
            $products = json_decode($result, true)["products"];
            $existing_products = $this->db->query('SELECT ProductId FROM products')
                                          ->result_array();
            $ext_prods = array_values_recursive($existing_products);

            $data = array();
            $added = 0;
            foreach($products as $product) {
                $curr_prod = array( 
                        'ProductId'  =>  $product["id"], 
                        'Name'  =>  $product["title"]
                    );
                // If exists, save for batch update
                if(in_array($product["id"],$ext_prods)) {
                    $data[] = $curr_prod;
                } else {
                    $this->db->insert('products', $curr_prod);
                    $added++;
                }    
            }
            // Update all existing products
            if(count($data))
            $this->db->update_batch('products', $data, 'ProductId'); 

            file_put_contents("updated_at_min", $now);
            echo "Products: ".count($data)." Updated. $added Added.";
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
