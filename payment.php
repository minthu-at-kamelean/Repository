<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of payment
 *
 * @author admin
 */
class payment {
    //put your code here
    
          public function actionPayment()
	{
		$data = $this->header(3);
                                    $objCheckout=new Checkout;
                                    $dateNow = getDateToTimezone();
                           	//var_dump($data['customer']);die;
		if(!Yii::app()->request->isPostRequest)
		{
			throw new CHttpException(404,'This page does not exist in our system.');
		}
		if(is_null($data['memberId']))
		{
			$this->redirect(Yii::app()->createUrl('/'));
			exit;
		}

		$deal = Yii::app()->request->getPost('Deal');
		foreach($deal as $dealval)
			if(empty($dealval))
				throw new CHttpException(500,'Please insert your valid information.');

		$data['offerId'] = intval($deal['offerId']);
		$data['customerId'] = intval($data['memberId']);
		
		$data['offer'] = Offer::model()->getOffer($data['offerId']);
		if(is_null($data['offer']))
		{
			throw new CHttpException(500,'Deal does not exist.');
		}
		else
		{
			if(intval($data['offer']->remaining_time) < 0)
			{
				throw new CHttpException(500,'Deal was expired.');
			}
			$data['price'] = $data['offer']->price;
		}

		// Check if user have not real in DB
		if(is_null($data["customer"]))
		{
			Customer::model()->Logout();
			Yii::App()->redirect(array('/deal/'.$data['offerId']));
			exit;
		}
		$cardno = strval($deal['cardno']);
		$cardholdername = strval($deal['cardholdername']);
		$expirymonth = strval($deal['expirymonth']);
		$expiryyear = strval($deal['expiryyear']);
		$cvccode = strval($deal['cvccode']);
		$cardexpire = strtotime('01-'.$expirymonth.'-'.$expiryyear);
		if($cardexpire === false)
			throw new CHttpException(500,'Credit card expire date does not valid in our system.');

		
                                   $cardtype = $objCheckout::identifyCardType($cardno);
                                    
                                    if($cardtype === false)
			throw new CHttpException(500,'Credit card number was invalid, Please re-check your Credit card number');
	
                                    
                                    $password = self::randomPassword();
		$decrypted_password = $password;
		$token = md5(uniqid());
                
                                    $customer = $data['customer'];
                                  
                                        $cardtypestring=$cardtype;
                                                     $paymentInfo=array(
                                                    'offerId' => $data['offerId'],
                                                    'cardno' => $cardno,
                                                    'cardholdername' => $cardholdername,
                                                    'cvccode' => $cvccode,
                                                    'cardexpire' => $cardexpire,
                                                    'cardexpireyear'=>$expiryyear,
                                                    'cardtype' => $cardtype,
                                                    'step' => 1,
                                                    'secret' => $password,
                                                    'offerPrice'=> $data['offer']->price,
                                                    'customerEmail'=>$customer->email
                                                    );
                                    
                                    
                                    $data['token'] = $token;
                                    
		$message = "[Subscription process] - Begin";
		Yii::log($message, "info", "system.web.DealController");

		if($data['customer']->isVIP())
		{
                                                      
			if(!Customer::model()->canPurchaseWeeklyOffer($data['offerId']))
				throw new CHttpException(404,'This offer isn\'t your weekly offer.');
			if( !Purchase::model()->canSelectOffer($data['customer']->id))
			{
				throw new CHttpException(500,'You are already select an offer');
			}
			/* VIP Buy Immediatly */
			$customer = $data['customer'];
			$tokenid = $token;
			$token = Yii::app()->session["token".$tokenid];

                                                     $transaction_id = $customer->id.'-weekly-'.$token['offerId'].'-'.$customer->email.'-'.$dateNow;
                                                     
                                                 
                                                                 $result=$objCheckout->initializedPaymentDetail($paymentInfo, false);
                                                                 //echo Yii::app()->session["customer_token"]; die();
                                                                 if($result['success']==true)
                                                                 {
                                                                                $token = md5(uniqid());
                                                                                
                                                                                Yii::app()->session["token".$token] = array(
                                                                                'offerId' => $data['offerId'],
                                                                                'cardno' => $cardno,
                                                                                'cardholdername' => $cardholdername,
                                                                                'cvccode' => $cvccode,
                                                                                'cardexpire' => $cardexpire,
                                                                                'cardtype' => $cardtype,
                                                                                'cardtypestring' => $cardtype,
                                                                                'step' => 1,
                                                                                'secret' => $password
                                                                                );
                                                                                $data['token'] = $token;
                                                                                
                                                                                $data['token'] = $token;
                                                                                $tokenid = $token;
                                                                    }
                                                   
                                          
                                                     // echo $cardtype; die();
			$purchase = new Purchase;
			$purchase->transaction_id = $transaction_id;
			if (!empty(Yii::app()->session["psp_transaction_id"]))
			{
                                                                   $purchase->psp_transaction_id=Yii::app()->session["psp_transaction_id"];
                                                      }
			
			$purchase->customer_id = $data['memberId'];
			$purchase->offer_id = $data['offerId'];
			$purchase->payment_amount = $data['offer']->price;
			$purchase->cur_payment_amount = $data['offer']->cur_price;
			$purchase->purchase_type = 'Weekly';
			$purchase->purchase_date = $dateNow;
			$purchase->payment_date = $dateNow;
			$purchase->payment_status = ($result['success']==true)?'Approved':'Declined';	// Never Declined
                                                      $purchase->card_type=$objCheckout->getCardTypeString($cardtype);
                                                    
                                                      $purchase->card_no = "xxxxxxxxxxxx".substr($cardno, strlen($cardno)-4, strlen($cardno));
                                                    
                                                      if($purchase->validate())
			{
				$purchase->save();
				if(!$result['success'])
				{
					$this->transaction_error($tokenid,$result['reason'],$purchase->id);
				}

				if(!array_key_exists("inmail", $_SESSION))
					$_SESSION["inmail"] = new IndividualEmail();
				$_SESSION["inmail"]->sent_mail_voucher($purchase,$purchase->card_no);				
                                                                        //var_dump($token);
                                                                        //die();
                
				$this->redirect(Yii::app()->createUrl('/account/purchasesuccess/'.$tokenid));
				exit;
			}
			else
			{
				die(CVarDumper::dump($purchase->errors,10,true));
			}

			/* END VIP Path */
			exit;
		}

		try
		{
			$message = "[Begin recurring payment process] - Initialization";
			Yii::log($message, "info", "system.web.DealController");

			$model = $data['customer'];

			// FACPG Buy Deal
			$message = "[Begin welcome payment process] - Initialization";
			Yii::log($message, "info", "system.web.DealController");
			 
                                                                 $result=$objCheckout->initializedPaymentDetail($paymentInfo,true);
                                                                 //echo Yii::app()->session["customer_token"]; die();
                                                                 if($result['success']==true)
                                                                 {
                                                                                $token = md5(uniqid());
                                                                                Yii::app()->session["token".$token] = array(
                                                                                'offerId' => $data['offerId'],
                                                                                'cardno' => $cardno,
                                                                                'cardholdername' => $cardholdername,
                                                                                'cvccode' => $cvccode,
                                                                                'cardexpire' => $cardexpire,
                                                                                'cardtype' => $cardtype,
                                                                                'cardtypestring' => $cardtype,
                                                                                'step' => 1,
                                                                                'secret' => $password
                                                                                );
                                                                                $data['token'] = $token;
                                                                                $data['token'] = $token;
                                                                                $tokenid = $token;
                                                                    }
                                                       
                                                       $tokenid = $token;
			 $message = "[End welcome payment process] - Authorize recurring payment success";
			Yii::log($message, "info", "system.web.DealController");
		
		} catch (Exception $e) {
			
			$message = "Payment provider API failed";
			Yii::log($message, "error", "system.web.DealController");
			
			//die(json_encode(array('success'=>false,'message'=>'Authorization has failed for this transaction. Please try again or conteact your bank for assistance.')));
			// die(json_encode(array('success'=>false,'message'=>$e->getMessage())));
			 $this->transaction_error($token,$e->getMessage(),0, false);
			 $this->redirect(array('/deal/'.$data['offerId']));
			 exit;
		}
		//var_dump($result);die;
                                    $dateNow = getDateToTimezone();
		$purchase = new Purchase;
		
		if (!empty(Yii::app()->session["psp_transaction_id"]))
		{
			    
                                                $purchase->transaction_id= $customer->id.'-weekly-'.$token['offerId'].'-'.$customer->email.'-'.$dateNow;
                                                $purchase->psp_transaction_id=Yii::app()->session["psp_transaction_id"];
                                                          
		}
		$purchase->customer_id = $data['memberId'];
		$purchase->offer_id = $data['offerId'];
		$purchase->payment_amount = $data['offer']->price;
		$purchase->cur_payment_amount = $data['offer']->cur_price;
		$purchase->purchase_date = $dateNow;
		$purchase->payment_date = $dateNow;
		$purchase->payment_status = ($result['success']==true)?'Approved':'Declined';	// Never Declined
		$purchase->card_type=$objCheckout->getCardTypeString($cardtype);
                                     
		$purchase->card_no = "xxxxxxxxxxxx".substr($cardno, strlen($cardno)-4, strlen($cardno));
		$purchase->claimed_picture = "";
		if($purchase->validate())
		{               
			$purchase->save();
			if (!$result['success'])
			{
				//die(json_encode(array('success'=>false,'message'=>$result['reason'])));
				$this->transaction_error($token,$result['reason'],$purchase->id, false);
                                                                        
				$this->redirect(array('/deal/'.$data['offerId']));
				exit;
			}
			//$date7D = date('Y-m-d H:i:s',strtotime('+7 days'));
			$date7D = date('Y-m-d H:i:s',strtotime('+15 days')); // TEMPORARY
			$date7D = getDateToTimezone($date7D);
			
			$dateNow = getDateToTimezone();
			
			$model->member_status = "VIP";
			$model->password = Customer::model()->passwordHash($password);
			$model->subscribe_date = $dateNow;
			$model->billing_date = $date7D;
			
			$model->expiry_date = date('Y-m-d H:i:s',$cardexpire);
			$model->unsubscribe_date = null;
			$model->cvc_code = $cvccode;
			
			$model->birthday = $dateNow;
                                                      $model->has_recurring=1;
                                                      $model->token_id=Yii::app()->session["customer_token"];
                                                      $model->card_type=$objCheckout->getCardTypeString($cardtype);
                          
                                                      $model->update();

			//unset(Yii::app()->session["token".strval($token)]);

			if(!array_key_exists("inmail", $_SESSION))
				$_SESSION["inmail"] = new IndividualEmail();

			$message = "[Purchase confirmation sending] - Initialization";
			Yii::log($message, "info", "system.web.DealController");
			$_SESSION["inmail"]->sent_purchase_confirmation($model,$purchase,$data['offer'],$password);
			$message = "[Purchase confirmation sending] - End";
			Yii::log($message, "info", "system.web.DealController");
			
			$message = "[Purchase survey sending] - Initialization";
			Yii::log($message, "info", "system.web.DealController");
			$_SESSION["inmail"]->sent_survey($model);
			$message = "[Purchase survey sending] - End";
			Yii::log($message, "info", "system.web.DealController");


			if(!array_key_exists("dailymail", $_SESSION))
				$_SESSION["dailymail"]=new DailyEmail();
			$_SESSION["dailymail"]->unsubscribe($model->email);

		}
		else
		{
			 die(CVarDumper::dump($purchase->errors,10,true));
		}

		/// END KAMELEAN CODE
		$message = "[Subscription process] - End";
		Yii::log($message, "info", "system.web.DealController");
                                    $this->redirect(array('/payment/'.$token));
	}
    
}
