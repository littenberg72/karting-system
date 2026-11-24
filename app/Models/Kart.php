<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Kart extends Model
{
	protected $fillable = [
		'number',
		'model',
		'status',
		'description',
	];
	public function maintenanceRecords()
	{
		return $this->hasMany(MaintenanceRecord::class);
	}
	public function raceParticipants()
	{
		return $this->hasMany(RaceParticipant::class);
	}
}
