<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class RaceSession extends Model
{
	protected $fillable = [
		'booking_id',
		'started_at',
		'finished_at',
		'status',
	];
	protected $casts = [
		'started_at' => 'datetime',
		'finished_at' => 'datetime',
	];
	public function booking()
	{
		return $this->belongsTo(Booking::class);
	}
	public function participants()
	{
		return $this->hasMany(RaceParticipant::class);
	}
}
