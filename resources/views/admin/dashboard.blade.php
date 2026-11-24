@extends('layouts.app')
@section('title','Панель администратора')
@section('content')
<section class="dashboard-wrap">
 <div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
   <div>
    <div class="eyebrow">APEX CONTROL / ADMIN</div>
    <h1>ПАНЕЛЬ <span>УПРАВЛЕНИЯ.</span></h1>
    <p class="muted mb-0">Бронирования, клиенты, сотрудники и настройки картодрома.</p>
   </div>
   <span class="badge text-bg-warning px-3 py-2">Администратор</span>
  </div>
  <nav class="admin-nav mb-4">
   <a href="#bookings">Бронирования</a>
   <a href="#clients">Клиенты</a>
   <a href="#staff">Сотрудники</a>
   <a href="#tariffs">Тарифы</a>
   <a href="#karts">Карты</a>
   <a href="#maintenance">Обслуживание</a>
   <a href="#payments">Платежи</a>
   <a href="#races">Заезды</a>
  </nav>
  <div class="row g-3 mb-5">
   @foreach([
    ['Всего броней', $stats['bookings']],
    ['Ожидают подтверждения', $stats['pending']],
    ['Клиенты', $stats['clients']],
    ['Сотрудники', $stats['staff']],
    ['Картов', $stats['karts']],
    ['Выручка', number_format($stats['revenue'], 0, ',', ' ') . ' ₽'],
   ] as [$label, $value])
    <div class="col-6 col-md-4 col-xl-2"><div class="stat-card h-100"><small>{{ $label }}</small><strong>{{ $value }}</strong></div></div>
   @endforeach
  </div>

  <section id="bookings" class="admin-section mb-5">
   <div class="panel">
    <div class="panel-head"><div><div class="eyebrow">BOOKING MANAGEMENT</div><h2>БРОНИРОВАНИЯ</h2></div><span>{{ $bookings->count() }} последних</span></div>
    @if($bookings->isEmpty())<p class="muted p-3">Пока бронирований нет.</p>@else
    <div class="table-responsive">
     <table class="table admin-table align-middle">
      <thead>
       <tr>
        <th>Клиент</th>
        <th>Тариф</th>
        <th>Дата и время</th>
        <th>Участники</th>
        <th>Стоимость</th>
        <th>Статус</th>
        <th>Управление</th>
       </tr>
      </thead>
      <tbody>
    @foreach($bookings as $booking)
	 <tr>
	 <td>
	 <strong>{{ $booking->user->name ?? 'Пользователь удалён' }}</strong>
	 <small class="d-block muted">{{ $booking->user->email ?? '—' }}</small>
	 </td>
	 <td>{{ $booking->tariff->name ?? 'Тариф удалён' }}</td>
	 <td>{{ $booking->start_at->format('d.m.Y H:i') }}</td>
	 <td>{{ $booking->participants_count }}</td>
	 <td>{{ number_format($booking->tariff_price * $booking->participants_count,0,',',' ') }} ₽</td>
	 <td>
	 <span class="status-pill status-{{ $booking->status }}">@switch($booking->status)
	 @case('pending')
	 Ожидает@break
	 @case('confirmed')
	 Подтверждено@break
	 @case('cancelled')
	 Отменено@break
	 @case('completed')
	 Завершено@break
	 @case('no_show')
	 Не явился@break
	 @default
	 {{ $booking->status }}@endswitch
	 </span>
	 </td>
     <td>@php
      $bookingStatusOptions = match($booking->status) {
       'pending' => ['pending' => 'Ожидает', 'confirmed' => 'Подтвердить', 'cancelled' => 'Отменить'],
       'confirmed' => $booking->raceSession && in_array($booking->raceSession->status, ['planned', 'running'])
        ? ['confirmed' => 'Подтверждено']
        : ['confirmed' => 'Подтверждено', 'cancelled' => 'Отменить', 'no_show' => 'Не явился'],
       default => [$booking->status => ['completed' => 'Завершено', 'cancelled' => 'Отменено', 'no_show' => 'Не явился'][$booking->status] ?? $booking->status],
      };
	 @endphp
	 <form method="POST" action="{{ route('admin.booking.update', $booking) }}" class="d-flex gap-2">
  @csrf
  @method('PATCH')
  <select class="form-select form-select-sm" name="status" aria-label="Статус бронирования">
   @foreach($bookingStatusOptions as $key => $label)
	   <option value="{{ $key }}" @selected($booking->status === $key)>{{ $label }}</option>
   @endforeach
	 </select>
	 <button class="btn btn-sm btn-accent">OK</button>
	 </form>
	 </td>
	 </tr>
    @endforeach
    </tbody></table></div>@endif
   </div>
  </section>

  <section id="clients" class="admin-section mb-5">
   <div class="panel"><div class="panel-head"><div><div class="eyebrow">CUSTOMER DATABASE</div><h2>КЛИЕНТЫ</h2></div><span>{{ $stats['clients'] }} зарегистрировано</span></div>
    @if($clients->isEmpty())<p class="muted p-3">Клиентов пока нет.</p>@else
	<div class="table-responsive">
	<table class="table admin-table align-middle">
	<thead>
	<tr>
	<th>Имя</th>
	<th>Электронная почта</th>
	<th>Телефон</th>
	<th>Бронирований</th>
	<th>Регистрация</th>
	<th>Изменить данные</th>
	</tr>
	</thead>
	<tbody>@foreach($clients as $client)
	<tr>
	<td>
	<strong>{{ $client->name }}</strong>
	</td>
	<td>{{ $client->email }}</td>
	<td>{{ $client->phone ?: '—' }}</td>
	<td>{{ $client->bookings_count }}</td>
	<td>{{ $client->created_at->format('d.m.Y') }}</td>
	<td>
	<details>
	<summary class="small">Редактировать</summary>
	<form class="mt-2" method="POST" action="{{ route('admin.client.update',$client) }}">@csrf @method('PATCH')<input class="form-control form-control-sm mb-1" name="name" value="{{ $client->name }}" required>
	<input class="form-control form-control-sm mb-1" type="email" name="email" value="{{ $client->email }}" required>
	<input class="form-control form-control-sm mb-1" name="phone" value="{{ $client->phone }}" placeholder="Телефон">
	<button class="btn btn-sm btn-accent">Сохранить</button>
	</form>
	</details>
	</td>
	</tr>@endforeach
	</tbody>
	</table>
	</div>@endif
   </div>
  </section>

  <section id="staff" class="admin-section mb-5">
   <div class="row g-4"><div class="col-lg-7"><div class="panel h-100"><div class="panel-head"><div><div class="eyebrow">TEAM DIRECTORY</div><h2>СОТРУДНИКИ</h2></div><span>{{ $staff->count() }} в списке</span></div>
    @if($staff->isEmpty())<p class="muted p-3">Сотрудники ещё не добавлены.</p>@else
	<div class="table-responsive">
	<table class="table admin-table align-middle">
	<thead>
	<tr>
	<th>Сотрудник</th>
	<th>Контакты</th>
	<th>Добавлен</th>
	<th>Редактирование</th>
	</tr>
	</thead>
	<tbody>@foreach($staff as $member)
	<tr>
	<td>
	<strong>{{ $member->name }}</strong>
	</td>
	<td>{{ $member->email }}<small class="d-block muted">{{ $member->phone ?: 'Телефон не указан' }}</small>
	</td>
	<td>{{ $member->created_at->format('d.m.Y') }}</td>
	<td>
	<details>
	<summary class="small">Изменить</summary>
	<form class="mt-2" method="POST" action="{{ route('admin.staff.update',$member) }}">@csrf @method('PATCH')<input class="form-control form-control-sm mb-1" name="name" value="{{ $member->name }}" required>
	<input class="form-control form-control-sm mb-1" type="email" name="email" value="{{ $member->email }}" required>
	<input class="form-control form-control-sm mb-1" name="phone" value="{{ $member->phone }}" placeholder="Телефон">
	<input class="form-control form-control-sm mb-1" type="password" name="password" placeholder="Новый пароль (необязательно)">
	<input type="password" class="form-control form-control-sm mb-1" name="password_confirmation" placeholder="Повтор пароля">
	<button class="btn btn-sm btn-accent">Сохранить</button>
	</form>
	</details>
	</td>
	</tr>@endforeach
	</tbody>
	</table>
	</div>@endif
   </div></div>
   <div class="col-lg-5"><div class="panel"><div class="panel-head"><div><div class="eyebrow">NEW TEAM MEMBER</div><h2>ДОБАВИТЬ СОТРУДНИКА</h2></div></div>
    <form method="POST" action="{{ route('admin.staff.store') }}">@csrf
     <div class="mb-3"><label class="form-label">ФИО</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
     <div class="mb-3"><label class="form-label">Электронная почта</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" required></div>
     <div class="mb-3"><label class="form-label">Телефон</label><input class="form-control" name="phone" value="{{ old('phone') }}"></div>
     <div class="mb-3"><label class="form-label">Пароль</label><input class="form-control" type="password" name="password" minlength="8" required></div>
     <div class="mb-3"><label class="form-label">Повторите пароль</label><input class="form-control" type="password" name="password_confirmation" minlength="8" required></div>
     <button class="btn btn-accent w-100">Создать учётную запись</button>
    </form>
   </div></div></div>
  </section>

  <section id="tariffs" class="admin-section mb-5">
   <div class="row g-4"><div class="col-lg-7"><div class="panel h-100"><div class="panel-head"><div><div class="eyebrow">PRICE MANAGEMENT</div><h2>ТАРИФЫ</h2></div><span>{{ $tariffs->count() }} всего</span></div>
	@foreach($tariffs as $tariff)
	<div class="booking-row">
	<div>
	<h3>{{ $tariff->name }}</h3>
	<small>{{ $tariff->duration }} минут · {{ number_format($tariff->price,0,',',' ') }} ₽</small>
	</div>
	<form method="POST" action="{{ route('admin.tariff.toggle', $tariff) }}">
		@csrf
		@method('PATCH')
		<button class="btn btn-sm {{ $tariff->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}">
			{{ $tariff->is_active ? 'Активен' : 'Скрыт' }}
		</button>
	</form>
	</div>@endforeach
   </div></div><div class="col-lg-5"><div class="panel"><div class="panel-head"><div><div class="eyebrow">NEW PRICE PLAN</div><h2>НОВЫЙ ТАРИФ</h2></div></div>
	<form method="POST" action="{{ route('admin.tariff.store') }}">@csrf<div class="mb-3">
	<label class="form-label">Название</label>
	<input class="form-control" name="name" required>
	</div>
	<div class="mb-3">
	<label class="form-label">Описание</label>
	<textarea class="form-control" name="description" rows="3">
	</textarea>
	</div>
	<div class="row">
	<div class="col-6 mb-3">
	<label class="form-label">Цена, ₽</label>
	<input class="form-control" type="number" step="0.01" min="0.01" name="price" required>
	</div>
	<div class="col-6 mb-3">
	<label class="form-label">Минуты</label>
	<input class="form-control" type="number" min="1" max="1440" name="duration" required>
	</div>
	</div>
	<button class="btn btn-accent w-100">Добавить тариф</button>
	</form>
   </div></div></div>
  </section>

  <section id="karts" class="admin-section mb-5">
   <div class="row g-4">
    <div class="col-lg-7"><div class="panel h-100"><div class="panel-head"><div><div class="eyebrow">FLEET MANAGEMENT</div><h2>ПАРК КАРТОВ</h2></div><span>{{ $karts->count() }} единиц</span></div>
     <div class="table-responsive"><table class="table admin-table align-middle"><thead><tr><th>Номер</th><th>Модель</th><th>Статус</th><th>Обслуживаний</th><th>Сохранить</th></tr></thead><tbody>
	  @foreach($karts as $kart)
	  @php $kartForm='kart-form-'.$kart->id;
	  @endphp
	  <tr>
	  <td>
	  <form id="{{ $kartForm }}" method="POST" action="{{ route('admin.kart.update',$kart) }}">@csrf @method('PATCH')</form>
	  <input form="{{ $kartForm }}" class="form-control form-control-sm" name="number" value="{{ $kart->number }}" required>
	  </td>
	  <td>
	  <input form="{{ $kartForm }}" class="form-control form-control-sm" name="model" value="{{ $kart->model }}">
	  </td>
	  <td>
	  <select form="{{ $kartForm }}" class="form-select form-select-sm" name="status">
   @foreach([
    'available' => 'Свободен',
    'in_use' => 'На трассе',
    'maintenance' => 'Обслуживание',
    'inactive' => 'Неактивен',
   ] as $key => $label)
	  <option value="{{ $key }}" @selected($kart->status===$key)>{{ $label }}</option>@endforeach
	  </select>
	  </td>
	  <td>{{ $kart->maintenance_records_count }}</td>
	  <td>
	  <button form="{{ $kartForm }}" class="btn btn-sm btn-accent">OK</button>
	  </td>
	  </tr>@endforeach
     </tbody></table></div>
    </div></div>
    <div class="col-lg-5"><div class="panel"><div class="panel-head"><div><div class="eyebrow">NEW KART</div><h2>ДОБАВИТЬ КАРТ</h2></div></div><form method="POST" action="{{ route('admin.kart.store') }}">@csrf
	 <div class="mb-2">
	 <label class="form-label">Номер</label>
	 <input class="form-control" name="number" required maxlength="20">
	 </div>
	 <div class="mb-2">
	 <label class="form-label">Модель</label>
	 <input class="form-control" name="model" maxlength="100">
	 </div>
	 <div class="mb-2">
	 <label class="form-label">Описание</label>
	 <textarea class="form-control" name="description" rows="2">
	 </textarea>
	 </div>
	 <div class="mb-3">
	 <label class="form-label">Состояние</label>
	 <select class="form-select" name="status">
	 <option value="available">Свободен</option>
	 <option value="maintenance">Обслуживание</option>
	 <option value="inactive">Неактивен</option>
	 </select>
	 </div>
	 <button class="btn btn-accent w-100">Добавить карт</button>
    </form></div></div>
   </div>
  </section>

  <section id="maintenance" class="admin-section mb-5">
   <div class="row g-4">
    <div class="col-lg-7">
     <div class="panel">
      <div class="panel-head">
       <div>
        <div class="eyebrow">SERVICE HISTORY</div>
        <h2>ИСТОРИЯ ОБСЛУЖИВАНИЯ</h2>
       </div>
      </div>
   <div class="table-responsive">
   <table class="table admin-table align-middle">
   <thead>
   <tr>
   <th>Карт</th>
   <th>Дата</th>
   <th>Работы</th>
   <th>Стоимость</th>
   </tr>
   </thead>
   <tbody>@forelse($maintenance as $record)
   <tr>
   <td>{{ $record->kart->number }}</td>
   <td>{{ $record->maintenance_date->format('d.m.Y') }}</td>
   <td>{{ $record->description }}</td>
   <td>{{ $record->cost ? number_format($record->cost,2,',',' ') . ' ₽' : '—' }}</td>
   </tr>@empty
   <tr>
   <td colspan="4" class="muted">Записей пока нет.</td>
   </tr>@endforelse
   </tbody>
   </table>
   </div>
  </div>
  </div>
  <div class="col-lg-5">
  <div class="panel">
  <div class="panel-head">
  <div>
  <div class="eyebrow">NEW SERVICE RECORD</div>
  <h2>ЗАПИСАТЬ ОБСЛУЖИВАНИЕ</h2>
  </div>
  </div>
  <form method="POST" action="{{ route('admin.maintenance.store') }}">@csrf
   <div class="mb-2">
   <label class="form-label">Карт</label>
   <select class="form-select" name="kart_id" required>@foreach($karts as $kart)
   <option value="{{ $kart->id }}">№{{ $kart->number }} — {{ $kart->model }}</option>@endforeach
   </select>
   </div>
   <div class="mb-2">
   <label class="form-label">Дата</label>
   <input class="form-control" type="date" name="maintenance_date" value="{{ now()->toDateString() }}" required>
   </div>
   <div class="mb-2">
   <label class="form-label">Описание работ</label>
   <textarea class="form-control" name="description" rows="3" required>
   </textarea>
   </div>
   <div class="mb-3">
   <label class="form-label">Стоимость, ₽</label>
   <input class="form-control" type="number" min="0" step="0.01" name="cost">
   </div>
   <button class="btn btn-accent w-100">Сохранить запись</button>
  </form></div></div></div></section>

  <section id="payments" class="admin-section mb-5"><div class="panel"><div class="panel-head"><div><div class="eyebrow">PAYMENT CONTROL</div><h2>ПЛАТЕЖИ</h2></div><span>{{ $payments->count() }} последних</span></div>
   <div class="table-responsive">
   <table class="table admin-table align-middle">
   <thead>
   <tr>
   <th>№</th>
   <th>Клиент / бронь</th>
   <th>Сумма</th>
   <th>Способ</th>
   <th>Статус</th>
   <th>Обновить</th>
   </tr>
   </thead>
   <tbody>@forelse($payments as $payment)
   <tr>
   <td>#{{ $payment->id }}</td>
   <td>{{ $payment->booking->user->name ?? '—' }}<small class="d-block muted">Бронь #{{ $payment->booking_id }}</small>
   </td>
   <td>{{ number_format($payment->amount,2,',',' ') }} ₽</td>
   <td>{{ ['cash'=>'Наличные','card'=>'Карта','online'=>'Онлайн'][$payment->method] ?? $payment->method }}</td>
   <td>{{ ['pending'=>'Ожидает','paid'=>'Оплачен','refunded'=>'Возврат'][$payment->status] ?? $payment->status }}</td>
   <td>@php
    $paymentStatusOptions = match($payment->status) {
     'pending' => ['pending' => 'Ожидает', 'paid' => 'Оплачен'],
     'paid' => ['paid' => 'Оплачен', 'refunded' => 'Возврат'],
     default => ['refunded' => 'Возврат'],
    };
   @endphp
   <form method="POST" action="{{ route('admin.payment.update',$payment) }}" class="d-flex gap-2">@csrf @method('PATCH')<select class="form-select form-select-sm" name="status">@foreach($paymentStatusOptions as $key=>$label)
   <option value="{{ $key }}" @selected($payment->status===$key)>{{ $label }}</option>@endforeach
   </select>
   <button class="btn btn-sm btn-accent">OK</button>
   </form>
   </td>
   </tr>@empty
   <tr>
   <td colspan="6" class="muted">Платежей пока нет.</td>
   </tr>@endforelse
   </tbody>
   </table>
   </div>
  </div></section>

  <section class="admin-section mb-5">
  <div class="panel">
  <div class="panel-head">
  <div>
  <div class="eyebrow">REGISTER PAYMENT</div>
  <h2>ЗАРЕГИСТРИРОВАТЬ ОПЛАТУ</h2>
  </div>
  </div>
  <div class="row g-3">@foreach($bookings->whereIn('status',['confirmed','completed']) as $booking)
  @php $total=(float)$booking->tariff_price*$booking->participants_count; $committed=(float)$booking->payments->whereIn('status',['paid','pending'])->sum('amount'); $remaining=max(0,$total-$committed);
  @endphp
  @if($remaining>0)
  <div class="col-md-6 col-xl-4">
  <div class="booking-row d-block">
  <strong>Бронь #{{ $booking->id }} · {{ $booking->user->name }}</strong>
  <div class="small muted mb-2">К оплате: {{ number_format($remaining,2,',',' ') }} ₽</div>
  <form method="POST" action="{{ route('admin.payment.store',$booking) }}">@csrf<div class="mb-2">
  <input class="form-control form-control-sm" type="number" name="amount" min="0.01" max="{{ number_format($remaining,2,'.','') }}" step="0.01" value="{{ number_format($remaining,2,'.','') }}" required>
  </div>
  <div class="row g-2 mb-2">
  <div class="col-6">
  <select class="form-select form-select-sm" name="method">
  <option value="cash">Наличные</option>
  <option value="card">Карта</option>
  <option value="online">Онлайн</option>
  </select>
  </div>
  <div class="col-6">
  <select class="form-select form-select-sm" name="status">
  <option value="paid">Оплачено</option>
  <option value="pending">Ожидает</option>
  </select>
  </div>
  </div>
  <input class="form-control form-control-sm mb-2" name="transaction_id" placeholder="ID транзакции (необязательно)">
  <button class="btn btn-sm btn-accent w-100">Зарегистрировать</button>
  </form>
  </div>
  </div>@endif
  @endforeach
  </div>
  </div></section>

  <section id="races" class="admin-section mb-5">
   <div class="panel">
    <div class="panel-head">
     <div>
      <div class="eyebrow">RACE HISTORY</div>
      <h2>ЗАЕЗДЫ И РЕЗУЛЬТАТЫ</h2>
     </div>
     <span>{{ $races->count() }} последних</span>
    </div>
   @forelse($races as $race)
   <details class="booking-row">
   <summary>
   <strong>Заезд #{{ $race->id }}</strong> · {{ $race->booking->user->name ?? 'Клиент' }} ·
   {{ [
    'planned' => 'Запланирован',
    'running' => 'Идёт',
    'completed' => 'Завершён',
    'cancelled' => 'Отменён',
   ][$race->status] ?? $race->status }}
   </summary>
   <div class="table-responsive mt-3">
   <table class="table admin-table">
   <thead>
   <tr>
   <th>Участник</th>
   <th>Карт</th>
   <th>Круг</th>
   <th>Время, сек.</th>
   </tr>
   </thead>
   <tbody>@foreach($race->participants as $participant)
   @forelse($participant->lapResults as $lap)
   <tr>
   <td>{{ $participant->full_name }}</td>
   <td>{{ $participant->kart->number }}</td>
   <td>{{ $lap->lap_number }}</td>
   <td>{{ number_format($lap->lap_time,3,',',' ') }}</td>
   </tr>@empty
   <tr>
   <td>{{ $participant->full_name }}</td>
   <td>{{ $participant->kart->number }}</td>
   <td colspan="2" class="muted">Результатов кругов пока нет</td>
   </tr>@endforelse
   @endforeach
   </tbody>
   </table>
   </div>
   </details>@empty
   <p class="muted p-3">Заездов пока нет.</p>@endforelse
  </div></section>

 </div>
</section>
@endsection
