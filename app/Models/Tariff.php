<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tariff extends Model { protected $fillable=['name','description','price','duration','is_active']; protected $casts=['price'=>'decimal:2','duration'=>'integer','is_active'=>'boolean']; public function bookings(){ return $this->hasMany(Booking::class); } }
