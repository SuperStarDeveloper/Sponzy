<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\Subscriptions;
use App\Models\Notifications;
use App\Models\User;
use Fahim\PaypalIPN\PaypalIPNListener;
use App\Helper;
use Mail;
use Carbon\Carbon;
use App\Models\PaymentGateways;
use App\Models\Transactions;

class PayPalController extends Controller
{
  use Traits\Functions;

  public function __construct(AdminSettings $settings, Request $request) {
		$this->settings = $settings::first();
		$this->request = $request;
	}

  /**
   * Show/Send form PayPal
   *
   * @return response
   */
    public function show()
    {

    if (! $this->request->expectsJson()) {
        abort(404);
    }

    // Find the User
    $user = User::whereVerifiedId('yes')
        ->whereId($this->request->id)
          ->where('id', '<>', auth()->id())
          ->firstOrFail();

    // Check if Plan exists
    $plan = $user->plans()
      ->whereInterval($this->request->interval)
       ->whereStatus('1')
         ->firstOrFail();

      // Get Payment Gateway
      $payment = PaymentGateways::findOrFail($this->request->payment_gateway);

      // Verify environment Sandbox or Live
      if ($payment->sandbox == 'true') {
				$action = "https://www.sandbox.paypal.com/cgi-bin/webscr";
				} else {
				$action = "https://www.paypal.com/cgi-bin/webscr";
				}

        $urlSuccess = url('buy/subscription/success', $user->username).'?paypal=1';
  			$urlCancel   = url('buy/subscription/cancel', $user->username);
  			$urlPaypalIPN = url('paypal/ipn');

        switch ($plan->interval) {
          case 'weekly':
            $interval = 'D';
            $interval_count = 7;
            break;

          case 'monthly':
            $interval = 'M';
            $interval_count = 1;
            break;

          case 'quarterly':
            $interval = 'M';
            $interval_count = 3;
            break;

          case 'biannually':
            $interval = 'M';
            $interval_count = 6;
            break;

          case 'yearly':
            $interval = 'Y';
            $interval_count = 1;
            break;
        }

  			return response()->json([
  					        'success' => true,
  					        'insertBody' => '<form id="form_pp" name="_xclick" action="'.$action.'" method="post"  style="display:none">
                    <input type="hidden" name="cmd" value="_xclick-subscriptions"/>
                    <input type="hidden" name="return" value="'.$urlSuccess.'">
  					        <input type="hidden" name="cancel_return"   value="'.$urlCancel.'">
              			<input type="hidden" name="notify_url" value="'.$urlPaypalIPN.'">
                    <input type="hidden" name="no_shipping" value="1">
                    <input type="hidden" name="currency_code" value="'.$this->settings->currency_code.'">
              			<input type="hidden" name="item_name" value="'.trans('general.subscription_for').' @'.$user->username.'">
                    <input type="hidden" name="custom" value="id='.$this->request->id.'&amount='.$plan->price.'&subscriber='.auth()->id().'&name='.auth()->user()->name.'&plan='.$plan->name.'&taxes='.auth()->user()->taxesPayable().'">
              			<input type="hidden" name="a3" value="'.Helper::amountGross($plan->price).'"/>
              			<input type="hidden" name="p3" value="'.$interval_count.'"/>
              			<input type="hidden" name="t3" value="'.$interval.'"/>
              			<input type="hidden" name="src" value="1"/>
              			<input type="hidden" name="rm" value="2"/>
                    <input type="hidden" name="business" value="'.$payment->email.'">
              			</form> <script type="text/javascript">document._xclick.submit();</script>',
  					    ]);
    }

    /**
     * PayPal IPN
     *
     * @return void
     */
    public function paypalIpn() {

      $ipn = new PaypalIPNListener();

			$ipn->use_curl = false;

      $payment = PaymentGateways::find(1);

			if ($payment->sandbox == 'true') {
				// SandBox
				$ipn->use_sandbox = true;
				} else {
				// Real environment
				$ipn->use_sandbox = false;
				}

	    $verified = $ipn->processIpn();

			$custom  = $_POST['custom'];
			parse_str($custom, $data);

			$payment_status = $_POST['payment_status'];
      $txn_type       = $_POST['txn_type'];
      $txn_id         = $_POST['txn_id'];
      $subscr_id      = $_POST['subscr_id'];

      $user = User::find($data['id']);

      // Check if Plan exists
      $plan = $user->plans()
        ->whereName($data['plan'])
        ->first();

      // Admin and user earnings calculation
      $earnings = $this->earningsAdminUser($user->custom_fee, $data['amount'], $payment->fee, $payment->fee_cents);

if ($verified) {

  switch ($txn_type) {

    case 'subscr_payment':

		if ($payment_status == 'Completed') {

      // Check outh POST variable and insert in DB
			$verifiedTxnId = Transactions::where('txn_id', $txn_id)->first();

			if (! isset($verifiedTxnId)) {

        // Subscription
        $subscription = Subscriptions::where('subscription_id', $subscr_id)->first();

        if (! isset($subscription)) {
          // Insert DB
          $subscription          = new Subscriptions();
          $subscription->user_id = $data['subscriber'];
          $subscription->stripe_price = $data['plan'];
          $subscription->subscription_id = $subscr_id;
          $subscription->ends_at = $user->planInterval($plan->interval);
          $subscription->interval = $plan->interval;
          $subscription->save();

          // Send Notification to User --- destination, author, type, target
          Notifications::send($data['id'], $data['subscriber'], '1', $data['id']);

        } else {
          $subscription->ends_at = $user->planInterval($plan->interval);
          $subscription->save();

          // Send Notification to User --- destination, author, type, target
          Notifications::send($data['id'], $data['subscriber'], 12, $data['subscriber']);
        }

        // Insert Transaction
        $this->transaction(
            $txn_id,
            $data['subscriber'],
            $subscription->id,
            $data['id'],
            $data['amount'],
            $earnings['user'],
            $earnings['admin'],
            'PayPal',
            'subscription',
            $earnings['percentageApplied'],
            $data['taxes']
          );

        // Add Earnings to User
        $user->increment('balance', $earnings['user']);

			}// <--- Verified Txn ID
    } // <-- Payment status

    break;

    case 'subscr_cancel':

    // Subscription
    $subscription = Subscriptions::where('subscription_id', $subscr_id)->first();
    $subscription->cancelled = 'yes';
    $subscription->save();

    break;

   }// switch
  }// Verified

    }//<----- End Method paypalIpn()
}
