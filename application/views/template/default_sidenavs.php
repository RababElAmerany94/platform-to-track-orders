<?php
$CI =& get_instance();
$CI->load->helper('general_helper');
$userdata = $CI->session->userdata();
$name = $img = "";

if (isset($userdata) && isset($userdata['full_name']) && isset($userdata['email'])) {
	$name = $userdata['full_name'];
	$img = 'https://www.gravatar.com/avatar/' . md5($userdata['email']);
}

?>
<div class="col-md-3 left_col menu_fixed">
    <div class="left_col scroll-view">
        <div class="navbar nav_title" style="border: 0;">
            <a href="<?php echo base_url(); ?>" class="site_title">
                <center><span style="font-size: 20px;"><?= settings('app_name') ?></span></center>
            </a>
        </div>

        <div class="clearfix"></div>
        <!-- menu profile quick info -->
        <div class="profile clearfix">
            <div class="profile_pic">
                <img src="<?= $img ?>" alt="..." class="img-circle profile_img">
            </div>
            <div class="profile_info">
                <span><?php echo $this->lang->line('Welcome'); ?>,</span>
                <h2><?= $name ?></h2>
            </div>
        </div>
        <!-- /menu profile quick info -->
        <br>
        <!-- Sidebar Menu -->
        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
            <div class="menu_section">
                <h3>Menu</h3>
                <ul class="nav side-menu">
					<?php if (can_list("Dashboard")): ?>
                    <li><a href="<?php echo base_url('dashboard/') ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
						<?php endif; ?>
                    <li><a><i class="fa fa-truck"></i> <?php echo $this->lang->line('E-Commerce'); ?> <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
							<?php if (can_list("Orders")): ?>
                                <li><a href="<?php echo base_url('orders/index/new') ?>"><?php echo $this->lang->line('New Orders'); ?></a></li>
							<?php endif; ?>
							<?php if (can_list("Orders")): ?>
                                <li><a href="<?php echo base_url('orders/') ?>"><?php echo $this->lang->line('All Orders'); ?></a></li>
							<?php endif; ?>
							<?php if (can_add("Shipments")): ?>
                                <li><a href="<?php echo base_url('shipments/index/add') ?>">Add Shipments</a></li>
							<?php endif; ?>
							<?php if (can_list("Shipments")): ?>
                                <li><a href="<?php echo base_url('shipments/') ?>"><?php echo $this->lang->line('Show Shipments'); ?></a></li>
							<?php endif; ?>
							<?php if (can_list("Products")): ?>
                                <li><a href="<?php echo base_url('products/index/add') ?>">Add Products</a></li>
							<?php endif; ?>
							<?php if (can_list("Products")): ?>
                                <li><a href="<?php echo base_url('products/') ?>">Show Products</a></li>
							<?php endif; ?>
                        </ul>
                    </li>

                    <li>
                        <a><i class="fa fa-line-chart"></i> <?php echo $this->lang->line('Stats'); ?> <span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
	                        <?php if (can_list("DeliveryRates")): ?>
                                <li><a href="<?php echo base_url('DeliveryRates/') ?>">Delivery Rates</a></li>
	                        <?php endif; ?>
	                        <?php if (can_list("ProductStats")): ?>
                                <li><a href="<?php echo base_url('ProductStats/') ?>">Product Stats</a></li>
	                        <?php endif; ?>
                        </ul>
                    </li>

                    <?php if (can_list("Users") || can_list("Roles")): ?>
                        <li><a><i class="fa fa-group"></i> Users <span class="fa fa-chevron-down"></span></a>
                            <ul class="nav child_menu">
								<?php if (can_list("Users")): ?>
                                    <li><a href="<?php echo base_url('users/index/add') ?>">Add Users</a></li>
								<?php endif; ?>
								<?php if (can_list("Users")): ?>
                                    <li><a href="<?php echo base_url('users/') ?>">Show Users</a></li>
								<?php endif; ?>
								<?php if (can_list("Roles")): ?>
                                    <li><a href="<?php echo base_url('roles/index/add') ?>">Add Roles</a></li>
								<?php endif; ?>
								<?php if (can_list("Roles")): ?>
                                    <li><a href="<?php echo base_url('roles/') ?>">Show Roles</a></li>
								<?php endif; ?>
                            </ul>
                        </li>
					<?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /Sidebar Menu -->
    </div>
</div>
