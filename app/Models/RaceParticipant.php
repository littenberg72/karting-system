<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RaceParticipant extends Model { protected $fillable=['race_session_id','booking_participant_id','kart_id','full_name','status']; public function session(){ return $this->belongsTo(RaceSession::class,'race_session_id'); } public function bookingParticipant(){ return $this->belongsTo(BookingParticipant::class); } public function kart(){ return $this->belongsTo(Kart::class); } public function lapResults(){ return $this->hasMany(LapResult::class); } }
