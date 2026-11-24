<?php

namespace App\Services;

use App\Models\Kart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class KartStatusService
{
	public function update(Kart $kart, array $attributes): void
	{
		DB::transaction(function () use ($kart, $attributes) {
			$lockedKart = Kart::whereKey($kart->id)->lockForUpdate()->firstOrFail();
			$newStatus = $attributes['status'];
			if ($lockedKart->status !== 'in_use' && $newStatus === 'in_use') {
				throw ValidationException::withMessages(['status' => 'Статус «на трассе» назначается автоматически при создании заезда.']);
			}
			if ($lockedKart->status === 'in_use' && $newStatus !== 'in_use' && $lockedKart->raceParticipants()->whereHas('session', fn($query) => $query->whereIn('status', [
				'planned',
				'running',
			]))->exists()) {
				throw ValidationException::withMessages(['status' => 'Нельзя изменить статус карта, пока его заезд не завершён или не отменён.']);
			}
			$lockedKart->update($attributes);
		});
	}
}
