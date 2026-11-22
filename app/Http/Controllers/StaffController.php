<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Kart;
use App\Models\LapResult;
use App\Models\RaceParticipant;
use App\Models\RaceSession;
use App\Services\BookingStatusService;
use App\Services\KartStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function dashboard()
    {
        return view('staff.dashboard', [
            'bookings' => Booking::with(['user', 'tariff', 'participants', 'raceSession'])
                ->latest()->take(30)->get(),
            'karts' => Kart::orderBy('number')->get(),
            'races' => RaceSession::with(['booking.user', 'participants.kart', 'participants.lapResults'])
                ->latest()->take(30)->get(),
        ]);
    }

    public function updateBooking(Request $request, Booking $booking, BookingStatusService $bookingStatusService)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed,no_show',
        ]);
        $bookingStatusService->update($booking, $data['status']);

        return back()->with('success', 'Статус бронирования обновлён.');
    }

    public function kartStatus(Request $request, Kart $kart, KartStatusService $kartStatusService)
    {
        $data = $request->validate([
            'status' => 'required|in:available,in_use,maintenance,inactive',
        ]);

        $kartStatusService->update($kart, $data);

        return back()->with('success', 'Статус карта обновлён.');
    }

    public function createRace(Booking $booking)
    {
        $session = DB::transaction(function () use ($booking) {
            // Lock fleet rows before the booking row, matching the lock order used
            // by booking creation and reducing the chance of deadlocks.
            $fleet = Kart::whereNotIn('status', ['maintenance', 'inactive'])
                ->orderBy('id')->lockForUpdate()->get();
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== 'confirmed') {
                abort(422, 'Заезд можно создать только для подтверждённого бронирования.');
            }

            $existing = RaceSession::where('booking_id', $lockedBooking->id)->first();
            if ($existing) {
                return $existing;
            }

            $participants = $lockedBooking->participants()->get();
            if ($participants->isEmpty()) {
                abort(422, 'В бронировании нет участников.');
            }

            $available = $fleet->where('status', 'available')->sortBy('number')
                ->take($participants->count())->values();

            if ($available->count() < $participants->count()) {
                abort(422, 'Недостаточно свободных картов для всех участников.');
            }

            $race = RaceSession::create([
                'booking_id' => $lockedBooking->id,
                'status' => 'planned',
            ]);

            foreach ($participants as $index => $participant) {
                $race->participants()->create([
                    'booking_participant_id' => $participant->id,
                    'kart_id' => $available[$index]->id,
                    'full_name' => $participant->full_name,
                    'status' => 'registered',
                ]);
                $available[$index]->update(['status' => 'in_use']);
            }

            return $race;
        });

        return back()->with('success', 'Заезд #' . $session->id . ' создан, карты назначены участникам.');
    }

    public function updateRace(Request $request, RaceSession $raceSession)
    {
        $data = $request->validate(['status' => 'required|in:running,completed,cancelled']);

        DB::transaction(function () use ($data, $raceSession) {
            // Keep the same lock order as race creation: fleet -> booking -> race.
            $kartIds = RaceParticipant::where('race_session_id', $raceSession->id)
                ->orderBy('kart_id')->pluck('kart_id');
            Kart::whereIn('id', $kartIds)->orderBy('id')->lockForUpdate()->get();

            $bookingId = RaceSession::whereKey($raceSession->id)->value('booking_id');
            Booking::whereKey($bookingId)->lockForUpdate()->firstOrFail();
            $lockedRace = RaceSession::whereKey($raceSession->id)->lockForUpdate()->firstOrFail();
            $currentStatus = $lockedRace->status;

            $allowedTransitions = [
                'planned' => ['running', 'cancelled'],
                'running' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ];

            if (! in_array($data['status'], $allowedTransitions[$currentStatus] ?? [], true)) {
                abort(422, 'Недопустимый переход состояния заезда.');
            }

            $lockedRace->update([
                'status' => $data['status'],
                'started_at' => $data['status'] === 'running' ? now() : $lockedRace->started_at,
                'finished_at' => in_array($data['status'], ['completed', 'cancelled'], true) ? now() : null,
            ]);

            if (in_array($data['status'], ['completed', 'cancelled'], true)) {
                Kart::whereIn('id', $kartIds)
                    ->where('status', 'in_use')
                    ->update(['status' => 'available']);

                $lockedRace->booking()->update([
                    'status' => $data['status'] === 'completed' ? 'completed' : 'cancelled',
                ]);
            }
        });

        return back()->with('success', 'Состояние заезда обновлено.');
    }

    public function storeLapResult(Request $request, RaceSession $raceSession)
    {
        $data = $request->validate([
            'race_participant_id' => 'required|exists:race_participants,id',
            'lap_number' => 'required|integer|min:1|max:1000',
            'lap_time' => 'required|numeric|min:1|max:3600',
        ]);

        DB::transaction(function () use ($raceSession, $data) {
            $lockedRace = RaceSession::whereKey($raceSession->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRace->status === 'running', 422, 'Внести результат можно только во время заезда.');

            $participant = RaceParticipant::whereKey($data['race_participant_id'])
                ->where('race_session_id', $lockedRace->id)
                ->firstOrFail();

            LapResult::updateOrCreate(
                ['race_participant_id' => $participant->id, 'lap_number' => $data['lap_number']],
                ['lap_time' => $data['lap_time']]
            );
        });

        return back()->with('success', 'Результат круга сохранён.');
    }
}
