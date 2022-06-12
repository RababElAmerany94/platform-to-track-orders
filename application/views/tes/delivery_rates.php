<!-- First Section one Column -->
<div class="x_panel">
    <div class="row">
        <div class="col-md-12">
			<?= form_open('DeliveryRates/generate_report') ?>

            <div class="form-group col-md-4 col-xs-12">
                <label>Month: </label>
                <select id="month-select" class="form-control months" name="month">
                    <option value="0" selected disabled>Select month</option>
					<?php for ($m = 1; $m <= 12; ++$m): ?>
                        <option value="<?php echo $m ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
					<?php endfor; ?>
                </select>
            </div>

            <div class="form-group col-md-4 col-xs-12">
                <label>Year: </label>
				
				<?php $years = range(date("Y"), 2010); ?>
                <select id="year-select" class="form-control years" name="year">
                    <option value="0" selected disabled>Select year</option>
					<?php foreach ($years as $year): ?>
                        <option value="<?php echo $year ?>"><?php echo $year ?></option>
					<?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-xs-12 col-md-4">
				<?= form_submit(['name' => 'submit', 'id' => 'generate_report', 'disabled' => 'disabled', 'value' => 'Générer PDF', 'class' => 'btn btn-primary', 'style' => 'margin-top:25px;']) ?>
            </div>
			
			<?= form_close() ?>
        </div>

        <div class="col-md-12">
            <p id="loading" class="loading" style="display:none;">Chargement...</p>
        </div>
    </div>

    <hr>

    <div class="row" id="delivery-rates" style="display:none">
        <div class="col-xs-12 delivery-rates">
            <table class="table table-bordered table-hover" style="margin-top:25px;" id="delivery-rates-table">
                <thead>
                <tr>
                    <th>Delivery Agent</th>
                    <th>Delivered</th>
                    <th>No Response</th>
                    <th>Cancelled</th>
                    <th>Returned</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>
