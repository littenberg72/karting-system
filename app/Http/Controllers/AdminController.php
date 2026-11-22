<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Kart;
use App\Models\Payment;
use App\Models\MaintenanceRecord;
use App\Models\RaceSession;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Tariff;
use App\Models\User;
use App\Services\BookingStatusService;
use App\Services\KartStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'stats' => [
                'bookings' => Booking::count(),
                'pending' => Booking::where('status', 'pending')->count(),
                'clients' => User::whereHas('role', fn ($q) => $q->where('slug', 'client'))->count(),
                'staff' => User::whereHas('role', fn ($q) => $q->where('slug', 'staff'))->count(),
                'karts' => Kart::count(),
                'revenue' => Payment::where('status', 'paid')->sum('amount'),
            ],
            'bookings' => Booking::with(['user', 'tariff', 'payments', 'raceSession'])->latest('start_at')->limit(30)->get(),
            'clients' => User::withCount('bookings')->whereHas('role', fn ($q) => $q->where('slug', 'client'))->latest()->limit(50)->get(),
            'staff' => User::whereHas('role', fn ($q) => $q->where('slug', 'staff'))->latest()->get(),
            'tariffs' => Tariff::orderBy('price')->get(),
            'karts' => Kart::withCount('maintenanceRecords')->orderBy('number')->get(),
            'maintenance' => MaintenanceRecord::with('kart')->latest('maintenance_date')->limit(30)->get(),
            'payments' => Payment::with(['booking.user'])->latest()->limit(30)->get(),
            'races' => RaceSession::with(['booking.user', 'participants.kart', 'participants.lapResults'])->latest()->limit(30)->get(),
        ]);
    }

    public function updateBooking(Request $request, Booking $booking, BookingStatusService $bookingStatusService)
    {
        $data = $request->validate(['status' => 'required|in:pending,confirmed,cancelled,completed,no_show']);
        $bookingStatusService->update($booking, $data['status']);

        return back()->with('success', 'Статус бронирования обновлён.');
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        User::create([
            'role_id' => $staffRole->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);
        return back()->with('success', 'Учётная запись сотрудника создана.');
    }

    public function storeTariff(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'duration' => 'required|integer|min:1|max:1440',
        ]);
        Tariff::create($data + ['is_active' => true]);
        return back()->with('success', 'Тариф добавлен.');
    }

    public function toggleTariff(Tariff $tariff)
    {
        $tariff->update(['is_active' => ! $tariff->is_active]);
        return back()->with('success', 'Статус тарифа изменён.');
    }

    public function updateClient(Request $request, User $user)
    {
        abort_unless($user->isRole('client'), 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);
        $user->update($data);
        return back()->with('success', 'Данные клиента обновлены.');
    }

    public function updateStaff(Request $request, User $user)
    {
        abort_unless($user->isRole('staff'), 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        unset($data['password']);
        $user->update($data);
        return back()->with('success', 'Данные сотрудника обновлены.');
    }

    public function storeKart(Request $request)
    {
        $data = $request->validate([
            'number' => 'required|string|max:20|unique:karts,number',
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:available,maintenance,inactive',
        ]);
        Kart::create($data);
        return back()->with('success', 'Карт добавлен в парк.');
    }

    public function updateKart(Request $request, Kart $kart, KartStatusService $kartStatusService)
    {
        $data = $request->validate([
            'number' => 'required|string|max:20|unique:karts,number,' . $kart->id,
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:available,in_use,maintenance,inactive',
        ]);

        $kartStatusService->update($kart, $data);

        return back()->with('success', 'Данные карта обновлены.');
    }

    public function storeMaintenance(Request $request)
    {
        $data = $request->validate([
            'kart_id' => 'required|exists:karts,id',
            'description' => 'required|string|max:2000',
            'maintenance_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
        ]);
        DB::transaction(function () use ($data) {
            $kart = Kart::whereKey($data['kart_id'])->lockForUpdate()->firstOrFail();
            if ($kart->status === 'in_use') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'kart_id' => 'Нельзя отправить карт на обслуживание во время заезда.',
                ]);
            }
            MaintenanceRecord::create($data);
            $kart->update(['status' => 'maintenance']);
        });
        return back()->with('success', 'Запись об обслуживании добавлена. Карт переведён в обслуживание.');
    }

    public function storePayment(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,online',
            'status' => 'required|in:pending,paid',
            'transaction_id' => 'nullable|string|max:255',
        ]);
        $payment = DB::transaction(function () use ($booking, $data) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $total = (float) $lockedBooking->tariff_price * $lockedBooking->participants_count;
            $committed = (float) $lockedBooking->payments()->whereIn('status', ['paid', 'pending'])->sum('amount');
            $remaining = max(0, round($total - $committed, 2));
            if ((float) $data['amount'] > $remaining) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Сумма превышает остаток к оплате: ' . number_format($remaining, 2, '.', '') . ' ₽.']);
            }
            return $lockedBooking->payments()->create($data + ['paid_at' => $data['status'] === 'paid' ? now() : null]);
        });
        return back()->with('success', 'Платёж #' . $payment->id . ' зарегистрирован.');
    }

    public function updatePayment(Request $request, Payment $payment)
    {
        $data = $request->validate(['status' => 'required|in:pending,paid,refunded']);

        DB::transaction(function () use ($data, $payment) {
            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $allowedTransitions = [
                'pending' => ['pending', 'paid'],
                'paid' => ['paid', 'refunded'],
                'refunded' => ['refunded'],
            ];

            if (! in_array($data['status'], $allowedTransitions[$lockedPayment->status] ?? [], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Недопустимый переход статуса платежа.',
                ]);
            }

            if (in_array($data['status'], ['pending', 'paid'], true)) {
                $total = (float) $booking->tariff_price * $booking->participants_count;
                $otherCommitted = (float) $booking->payments()
                    ->whereIn('status', ['pending', 'paid'])
                    ->where('id', '!=', $lockedPayment->id)->sum('amount');
                if ($otherCommitted + (float) $lockedPayment->amount > $total) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'Нельзя активировать платёж: сумма активных платежей превысит стоимость бронирования.',
                    ]);
                }
            }

            $paidAt = match ($data['status']) {
                'paid' => $lockedPayment->paid_at ?? now(),
                'refunded' => $lockedPayment->paid_at,
                default => null,
            };

            $lockedPayment->update(['status' => $data['status'], 'paid_at' => $paidAt]);
        });

        return back()->with('success', 'Статус платежа обновлён.');
    }

}
