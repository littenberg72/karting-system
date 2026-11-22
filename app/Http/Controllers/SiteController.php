<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Kart;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SiteController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'tariffs' => Tariff::where('is_active', true)->orderBy('price')->take(3)->get(),
        ]);
    }

    public function tariffs()
    {
        return view('public.tariffs', [
            'tariffs' => Tariff::where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    public function account(Request $request)
    {
        $user = $request->user();
        if ($user->isRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isRole('staff')) {
            return redirect()->route('staff.dashboard');
        }

        return view('customer.dashboard', [
            'bookings' => $user->bookings()->with(['tariff', 'payments', 'raceSession.participants.lapResults'])
                ->latest()->get(),
        ]);
    }

    public function createBooking()
    {
        return view('customer.booking-create', [
            'tariffs' => Tariff::where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    public function storeBooking(Request $request)
    {
        $data = $request->validate([
            'tariff_id' => 'required|exists:tariffs,id',
            'start_at' => 'required|date|after:now',
            'participants' => 'required|array|min:1|max:8',
            'participants.*' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        $tariff = Tariff::where('is_active', true)->findOrFail($data['tariff_id']);
        $startAt = Carbon::parse($data['start_at']);
        $endAt = $startAt->copy()->addMinutes($tariff->duration);
        $participantCount = count($data['participants']);

        DB::transaction(function () use ($request, $data, $tariff, $startAt, $endAt, $participantCount) {
            // Lock the active fleet rows so concurrent booking requests cannot both
            // reserve the same remaining capacity at the same time.
            $fleet = Kart::whereNotIn('status', ['maintenance', 'inactive'])
                ->orderBy('id')->lockForUpdate()->get();

            if ($fleet->isEmpty()) {
                throw ValidationException::withMessages([
                    'start_at' => 'Сейчас нет доступных картов для бронирования.',
                ]);
            }

            $existingBookings = Booking::whereIn('status', ['pending', 'confirmed'])
                ->whereBetween('start_at', [$startAt->copy()->subDays(2), $endAt])
                ->withCount(['participants as actual_participants_count'])
                ->lockForUpdate()->get();

            $events = [
                ['time' => $startAt->timestamp, 'delta' => $participantCount],
                ['time' => $endAt->timestamp, 'delta' => -$participantCount],
            ];

            foreach ($existingBookings as $existing) {
                $existingStart = Carbon::parse($existing->start_at);
                $existingEnd = $existingStart->copy()->addMinutes((int) $existing->tariff_duration);

                if ($existingStart->lt($endAt) && $existingEnd->gt($startAt)) {
                    $events[] = ['time' => $existingStart->timestamp, 'delta' => (int) $existing->actual_participants_count];
                    $events[] = ['time' => $existingEnd->timestamp, 'delta' => -(int) $existing->actual_participants_count];
                }
            }

            // End events are processed before start events at the same timestamp:
            // one booking may begin exactly when another one ends.
            usort($events, fn ($a, $b) => ($a['time'] <=> $b['time']) ?: ($a['delta'] <=> $b['delta']));
            $occupied = 0;
            $maximumOccupied = 0;
            foreach ($events as $event) {
                $occupied += $event['delta'];
                $maximumOccupied = max($maximumOccupied, $occupied);
            }

            if ($maximumOccupied > $fleet->count()) {
                throw ValidationException::withMessages([
                    'start_at' => 'На выбранное время недостаточно свободных картов. Выберите другое время или уменьшите число участников.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'tariff_id' => $tariff->id,
                'start_at' => $startAt,
                'participants_count' => $participantCount,
                'tariff_price' => $tariff->price,
                'tariff_duration' => $tariff->duration,
                'status' => 'pending',
                'comment' => $data['comment'] ?? null,
            ]);

            foreach ($data['participants'] as $name) {
                $booking->participants()->create(['full_name' => $name]);
            }
        });

        return redirect()->route('account')->with('success', 'Заявка отправлена. Сотрудник подтвердит бронирование.');
    }
}
