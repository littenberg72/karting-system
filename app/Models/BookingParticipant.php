<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class BookingParticipant extends Model
{
	protected $fillable = [
		'booking_id',
		'full_name',
		'phone',
	];
	public function booking()
	{
		return $this->belongsTo(Booking::class);
	}
	public function raceParticipants()
	{
		return $this->hasMany(RaceParticipant::class);
	}
}
