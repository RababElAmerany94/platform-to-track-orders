<?php
$CI       =& get_instance();
$userdata = $CI->session->userdata();
$language = $this->config->item('DEFAULT_LANGUAGE');

if (isset($userdata) && $userdata['Id_Role'] == 2 && isset($userdata['language']) && in_array($language, $this->config->item('LANGUAGES'))) {
	$language = $userdata['language'];
}
?>
<!-- Top Nav -->
<div class="top_nav">
    <div class="nav_menu">
        <nav>
            <div class="nav toggle"><a id="menu_toggle"><i class="fa fa-bars"></i></a></div>
            <ul class="nav navbar-nav navbar-right">
                <li>
                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<?php
						$name = '';
						$img  = '';

						if (isset($userdata) && isset($userdata['full_name']) && isset($userdata['email'])) {
							$name = $userdata['full_name'];
							$img  = 'https://www.gravatar.com/avatar/' . md5($userdata['email']);
						}
						?>
                        <img src="<?= $img ?>" alt="">
						<?= $name ?> <span class="fa fa-angle-down"></span>
                    </a>

                    <ul class="dropdown-menu dropdown-usermenu pull-right">
                        <li><a href="users/index/edit/<?= $userdata['user_id'] ?>"><?php echo $this->lang->line('Profile'); ?></a></li>
                        <li><a href="settings"><span><?php echo $this->lang->line('Settings'); ?></span></a></li>
                        <li><a href="login/logout"><i class="fa fa-sign-out pull-right"></i><?php echo $this->lang->line('Logout'); ?></a></li>
                    </ul>
                </li>
            </ul>

			<?php if ($userdata['Id_Role'] == 2): ?>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
							<?php echo $this->lang->line(ucfirst($language)); ?>
                            <span class="fa fa-angle-down"></span>
                        </a>

                        <ul class="dropdown-menu dropdown-usermenu pull-right">
							<?php foreach ($this->config->item('LANGUAGES') as $language): ?>
                                <li><a href="language?lang=<?php echo $language; ?>"><?php echo $this->lang->line(ucfirst($language)); ?></a></li>
							<?php endforeach; ?>
                        </ul>
                    </li>
                </ul>

                <div style="clear:both;"></div>
			<?php endif; ?>
        </nav>
    </div>
</div>
<!-- /Top Nav -->
