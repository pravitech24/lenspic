<?php
return ['currency'=>'INR','mode'=>env('RAZORPAY_MODE','test'),'allow_live_outside_production'=>(bool)env('RAZORPAY_ALLOW_LIVE_OUTSIDE_PRODUCTION',false),'order_ttl_minutes'=>30];
