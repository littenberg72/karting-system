<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingStatusService
{
    /**
     * Change a booking status without allowing it to contradict its race session.
     */
    public function update(Booking $booking, string $newStatus): void
    {
        DB::transaction(function () use ($booking, $newStatus) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $race = $lockedBooking->raceSession()->lockForUpdate()->first();
            $currentStatus = $lockedBooking->status;

            if ($currentStatus === $newStatus) {
                return;
            }

            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['cancelled', 'no_show'],
                'completed' => [],
                'cancelled' => [],
                'no_show' => [],
            ];

            if (! in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Недопустимый переход статуса бронирования.',
                ]);
            }

            if ($race && in_array($race->status, ['planned', 'running'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Сначала завершите или отмените связанный заезд.',
                ]);
            }

            if ($newStatus === 'completed' || ($race && $race->status === 'completed')) {
                throw ValidationException::withMessages([
                    'status' => 'Завершение бронирования выполняется автоматически после завершения заезда.',
                ]);
            }

            if ($race && $race->status === 'cancelled' && $newStatus !== 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => 'Бронирование связано с отменённым заездом и не может быть восстановлено.',
                ]);
            }

            $lockedBooking->update(['status' => $newStatus]);
        });
    }
}
