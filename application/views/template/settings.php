<?php
$this->load->helper('general_helper');
if (can_edit('Settings')) {
	?>
    <form method="post" class="form-horizontal form-label-left">
		<?php foreach ($settings as $key => $value) { ?>
            <div class="form-group">
                <label for="<?= $key ?>" class="control-label col-md-3 col-sm-3 col-xs-12"><?= ucfirst(substr($key, 4)) ?></label>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <input id="<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= $value ?>"/>
                </div>
            </div>
		<?php } ?>
        <div class="ln_solid"></div>
        <div class="form-group">
            <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                <button type="submit" class="btn btn-success">Submit</button>
            </div>
        </div>
    </form>
<?php } ?>

<hr>

<style>
    .dot {
        height: 15px;
        width: 15px;
        background-color: #bbb;
        border-radius: 50%;
        display: inline-block;
    }
</style>

<div class="form-group">
    <h1>Notification</h1>
    <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
        <span class="dot" id="subscription-indicator"></span> <span id="subscription-indicator-text">Push Notification Enabled</span>
        <br>
        <br>
<!--        <span class="dot" style="background-color:#00CC00"></span> <span>Push Notification Disabled</span> -->
<!--        <br>-->
<!--        <br>-->
        <button class="btn btn-success" id="subscription-btn">Enable Push Notification</button>
    </div>
</div>
