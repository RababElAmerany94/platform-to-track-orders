<div class="row">
    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="x_panel tile fixed_height_320">
            <div class="x_title">
                <h2>Top DAs this Month</h2>

                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <lu>
					<?php foreach ($deliveryAgents as $deliveryAgent): ?>
                        <li>
							<?php echo $deliveryAgent['full_name'] ?> <?php echo round($deliveryAgent['percentage_delivered']) ?>%
                            (<?php echo $deliveryAgent['total_delivered'] ?>)
                        </li>
					<?php endforeach; ?>
                </lu>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="x_panel tile fixed_height_320 overflow_hidden">
            <div class="x_title">
                <h2>Top Products by City this Month</h2>

                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <lu>
					<?php foreach ($topProductsCities as $city => $topProducts): ?>
                    <li>
	                    <strong><?php echo ucwords($city) ?></strong>
                        
                        <lu>
							<?php foreach ($topProducts as $topProduct): ?>
                            <li style="margin-left:10px;list-style:circle">
                                <?php echo $topProduct['name'] ?>
                                <br>
                                <span style="margin-left:20px;">Orders: <?php echo $topProduct['total'] ?>. Percentage: <?php echo $topProduct['percentage'] ?>%</span>
                            </li>
				            <?php endforeach; ?>
                        </lu>
                    </li>
				    <?php endforeach; ?>
                </lu>

                <a href="/ProductStats" class="btn btn-primary btn-sm" style="margin-top:10px;">Go to Product Stats</a>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-4 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>New Orders</h2>

                <div class="clearfix"></div>
            </div>

            <div class="x_content">
                <lu>
					<?php foreach ($newOrders as $newOrder): ?>
                        <li style="border-bottom:1px solid #aaa;padding-bottom:5px;">
                            <strong>Order #<?php echo $newOrder['OrderNo'] ?></strong> <br>
							<?php echo $newOrder['Quantity'] ?> x <?php echo $newOrder['Name'] ?> <br>
                            Ship To: <?php echo $newOrder['RecipientFullName'] ?> <br>
                            City: <?php echo $newOrder['RecipientCity'] ?> <br>
                            <u><a href="/orders/index/edit/<?php echo $newOrder['OrderId'] ?>" class="btn btn-primary btn-sm">Assign Delivery Agent</a></u>
                        </li>
					<?php endforeach; ?>
                </lu>
            </div>
        </div>
    </div>
</div>

