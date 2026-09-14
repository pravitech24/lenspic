<?php
return ['currency'=>'INR','credit_scale'=>10,'credit_value_minor'=>100,'top_up_multiple_minor'=>500,'minimum_top_up_minor'=>500,'maximum_top_up_minor'=>10000000,'suggested_top_ups_minor'=>[10000,30000,50000,100000],'gst_basis_points'=>(int)env('WALLET_GST_BASIS_POINTS',1800),'usage_rates'=>['mail_notification_units'=>2,'whatsapp_notification_units'=>10,'liveness_check_units'=>20]];
