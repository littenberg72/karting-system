<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class MaintenanceRecord extends Model
{
	protected $fillable = [
		'kart_id',
		'description',
		'maintenance_date',
		'cost',
	];
	protected $casts = [
		'maintenance_date' => 'date',
		'cost' => 'decimal:2',
	];
	public function kart()
	{
		return $this->belongsTo(Kart::class);
	}
}
