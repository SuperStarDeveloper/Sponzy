<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
	protected $guarded = [];
  const UPDATED_AT = null;

	public function user()
	{
		return $this->belongsTo(User::class)->first();
	}

	public static function send($destination, $session_id, $type, $target)
	{
		$user = User::find($destination);

		if ($type == 5 && $user->notify_new_tip == 'no'
				|| $type == 6 && $user->notify_new_ppv == 'no')
				{
					return false;
				}

				self::create([
				'destination' => $destination,
				'author' => $session_id,
				'type' => $type,
				'target' => $target
			]);
	}

}
