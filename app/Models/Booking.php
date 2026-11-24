<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Booking extends Model
{
	protected $fillable = [
		'user_id',
		'tariff_id',
		'start_at',
		'participants_count',
		'tariff_price',
		'tariff_duration',
		'status',
		'comment',
	];
	protected $casts = [
		'start_at' => 'datetime',
		'tariff_price' => 'decimal:2',
		'tariff_duration' => 'integer',
	];
	public function user()
	{
		return $this->belongsTo(User::class);
	}
	public function tariff()
	{
		return $this->belongsTo(Tariff::class);
	}
	public function participants()
	{
		return $this->hasMany(BookingParticipant::class);
	}
	public function payments()
	{
		return $this->hasMany(Payment::class);
	}
	public function raceSession()
	{
		return $this->hasOne(RaceSession::class);
	}
}
