<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class LapResult extends Model
{
	protected $fillable = [
		'race_participant_id',
		'lap_number',
		'lap_time',
	];
	protected $casts = [
		'lap_time' => 'decimal:3',
		'lap_number' => 'integer',
	];
	public function participant()
	{
		return $this->belongsTo(RaceParticipant::class, 'race_participant_id');
	}
}
