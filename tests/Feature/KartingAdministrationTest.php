<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RaceParticipant;
use App\Models\RaceSession;
use App\Models\Kart;
use App\Models\Role;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartingAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);

        return User::create([
            'role_id' => $roleModel->id,
            'name' => 'Test ' . $role,
            'email' => $role . '@apex.test',
            'password' => 'Password123!',
        ]);
    }

    public function test_client_cannot_open_admin_panel(): void
    {
        $client = $this->makeUser('client');

        $this->actingAs($client)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_can_update_client_contact_details(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');

        $this->actingAs($admin)->patch(route('admin.client.update', $client), [
            'name' => 'Обновлённый клиент',
            'email' => 'updated@apex.test',
            'phone' => '+79990000000',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'name' => 'Обновлённый клиент',
            'email' => 'updated@apex.test',
            'phone' => '+79990000000',
        ]);
    }

    public function test_payment_cannot_exceed_booking_balance(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тестовый тариф',
            'description' => 'Для теста',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        Payment::create([
            'booking_id' => $booking->id,
            'amount' => 60,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->post(route('admin.payment.store', $booking), [
                'amount' => 50,
                'method' => 'card',
                'status' => 'paid',
            ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_booking_is_rejected_when_simultaneous_participants_exceed_fleet_capacity(): void
    {
        $client = $this->makeUser('client');
        $secondClient = User::create([
            'role_id' => $client->role_id,
            'name' => 'Second client',
            'email' => 'second@apex.test',
            'password' => 'Password123!',
        ]);
        $tariff = Tariff::create([
            'name' => 'Тариф на 15 минут',
            'description' => null,
            'price' => 100,
            'duration' => 15,
            'is_active' => true,
        ]);
        for ($number = 1; $number <= 8; $number++) {
            Kart::create(['number' => (string) $number, 'status' => 'available']);
        }

        $startAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->actingAs($client)->post(route('booking.store'), [
            'tariff_id' => $tariff->id,
            'start_at' => $startAt,
            'participants' => array_fill(0, 8, 'Участник'),
        ])->assertRedirect(route('account'));

        $this->actingAs($secondClient)->from(route('booking.create'))->post(route('booking.store'), [
            'tariff_id' => $tariff->id,
            'start_at' => $startAt,
            'participants' => ['Девятый участник'],
        ])->assertSessionHasErrors('start_at');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_pending_payment_cannot_be_marked_paid_if_other_payments_exceed_booking_total(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тестовый тариф оплаты',
            'description' => null,
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $first = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 60,
            'method' => 'cash',
            'status' => 'pending',
        ]);
        Payment::create([
            'booking_id' => $booking->id,
            'amount' => 50,
            'method' => 'card',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->patch(route('admin.payment.update', $first), ['status' => 'paid'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('payments', ['id' => $first->id, 'status' => 'pending']);
    }
    public function test_booking_cannot_be_manually_marked_completed_without_a_completed_race(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тестовый тариф завершения',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->patch(route('admin.booking.update', $booking), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    public function test_booking_cannot_be_cancelled_while_its_race_is_running(): void
    {
        $staff = $this->makeUser('staff');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф активного заезда',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $race = RaceSession::create([
            'booking_id' => $booking->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->actingAs($staff)->from(route('staff.dashboard'))
            ->patch(route('staff.booking.update', $booking), ['status' => 'cancelled'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('race_sessions', ['id' => $race->id, 'status' => 'running']);
    }

    public function test_completing_race_closes_booking_and_releases_its_kart(): void
    {
        $staff = $this->makeUser('staff');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф завершённого заезда',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $kart = Kart::create(['number' => 'R-01', 'status' => 'in_use']);
        $race = RaceSession::create([
            'booking_id' => $booking->id,
            'status' => 'running',
            'started_at' => now(),
        ]);
        RaceParticipant::create([
            'race_session_id' => $race->id,
            'kart_id' => $kart->id,
            'full_name' => 'Тестовый участник',
        ]);

        $this->actingAs($staff)->patch(route('staff.race.update', $race), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'completed']);
        $this->assertDatabaseHas('race_sessions', ['id' => $race->id, 'status' => 'completed']);
        $this->assertDatabaseHas('karts', ['id' => $kart->id, 'status' => 'available']);
    }

    public function test_planned_race_cannot_be_completed_without_starting(): void
    {
        $staff = $this->makeUser('staff');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф запланированного заезда',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $race = RaceSession::create(['booking_id' => $booking->id, 'status' => 'planned']);

        $this->actingAs($staff)->patch(route('staff.race.update', $race), ['status' => 'completed'])
            ->assertStatus(422);

        $this->assertDatabaseHas('race_sessions', ['id' => $race->id, 'status' => 'planned']);
    }

    public function test_pending_payment_cannot_be_refunded_before_it_is_paid(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф ожидающего платежа',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 100,
            'method' => 'cash',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->patch(route('admin.payment.update', $payment), ['status' => 'refunded'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_refunded_payment_cannot_be_activated_again(): void
    {
        $admin = $this->makeUser('admin');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф возврата',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 100,
            'method' => 'card',
            'status' => 'refunded',
        ]);

        $this->actingAs($admin)->from(route('admin.dashboard'))
            ->patch(route('admin.payment.update', $payment), ['status' => 'paid'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
    }

    public function test_staff_cannot_manually_put_available_kart_in_use(): void
    {
        $staff = $this->makeUser('staff');
        $kart = Kart::create(['number' => 'MAN-01', 'status' => 'available']);

        $this->actingAs($staff)->from(route('staff.dashboard'))
            ->patch(route('staff.kart.update', $kart), ['status' => 'in_use'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('karts', ['id' => $kart->id, 'status' => 'available']);
    }

    public function test_staff_cannot_release_kart_used_by_active_race(): void
    {
        $staff = $this->makeUser('staff');
        $client = $this->makeUser('client');
        $tariff = Tariff::create([
            'name' => 'Тариф карта на трассе',
            'price' => 100,
            'duration' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $client->id,
            'tariff_id' => $tariff->id,
            'start_at' => now()->addDay(),
            'participants_count' => 1,
            'tariff_price' => 100,
            'tariff_duration' => 10,
            'status' => 'confirmed',
        ]);
        $kart = Kart::create(['number' => 'BUSY-01', 'status' => 'in_use']);
        $race = RaceSession::create([
            'booking_id' => $booking->id,
            'status' => 'running',
            'started_at' => now(),
        ]);
        RaceParticipant::create([
            'race_session_id' => $race->id,
            'kart_id' => $kart->id,
            'full_name' => 'Участник активного заезда',
        ]);

        $this->actingAs($staff)->from(route('staff.dashboard'))
            ->patch(route('staff.kart.update', $kart), ['status' => 'available'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('karts', ['id' => $kart->id, 'status' => 'in_use']);
    }

}
