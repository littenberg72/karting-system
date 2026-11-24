<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
	use Notifiable;
	protected $fillable = [
		'role_id',
		'name',
		'email',
		'phone',
		'password',
	];
	protected $hidden = [
		'password',
		'remember_token',
	];
	protected $casts = [
		'email_verified_at' => 'datetime',
		'password' => 'hashed',
	];
	public function role()
	{
		return $this->belongsTo(Role::class);
	}
	public function bookings()
	{
		return $this->hasMany(Booking::class);
	}
	public function isRole(...$roles): bool
	{
		return in_array($this->role?->slug, $roles, true);
	}
}
