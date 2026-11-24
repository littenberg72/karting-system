@extends('layouts.app')

@section('title', 'Панель сотрудника')

@section('content')
	<section class="dashboard-wrap">
		<div class="container">
			<div class="eyebrow">OPERATIONS / STAFF</div>
			<h1>ПАНЕЛЬ <span>СОТРУДНИКА.</span></h1>
			<p class="muted mb-4">Управление заявками, заездами, результатами и состоянием картов.</p>

			<div class="panel mb-4">
				<div class="panel-head">
					<h2>БРОНИРОВАНИЯ</h2>
					<span>{{ $bookings->count() }} последних</span>
				</div>

				<div class="table-responsive">
					<table class="table table-dark table-hover align-middle">
						<thead>
							<tr>
								<th>№</th>
								<th>Клиент</th>
								<th>Дата</th>
								<th>Тариф</th>
								<th>Участники</th>
								<th>Статус</th>
								<th>Действия</th>
							</tr>
						</thead>
						<tbody>
							@forelse($bookings as $b)
								<tr>
									<td>#{{ $b->id }}</td>
									<td>{{ $b->user->name ?? '—' }}</td>
									<td>{{ $b->start_at->format('d.m H:i') }}</td>
									<td>{{ $b->tariff->name ?? '—' }}</td>
									<td>{{ $b->participants_count }}</td>
									<td>
										{{ [
											'pending' => 'Ожидает',
											'confirmed' => 'Подтверждено',
											'cancelled' => 'Отменено',
											'completed' => 'Завершено',
											'no_show' => 'Неявка',
										][$b->status] ?? $b->status }}
									</td>
									<td>
										<div class="d-flex flex-wrap gap-2">
											@if($b->status === 'pending')
												<form method="POST" action="{{ route('staff.booking.update', $b) }}">
													@csrf
													@method('PATCH')
													<input type="hidden" name="status" value="confirmed">
													<button class="btn btn-sm btn-outline-success">Подтвердить</button>
												</form>

												<form method="POST" action="{{ route('staff.booking.update', $b) }}">
													@csrf
													@method('PATCH')
													<input type="hidden" name="status" value="cancelled">
													<button class="btn btn-sm btn-outline-danger">Отменить</button>
												</form>
											@endif

											@if($b->status === 'confirmed' && !$b->raceSession)
												<form method="POST" action="{{ route('staff.race.create', $b) }}">
													@csrf
													<button class="btn btn-sm btn-accent">Создать заезд</button>
												</form>
											@elseif($b->raceSession)
												<span class="small muted">Заезд #{{ $b->raceSession->id }}</span>
											@endif
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="muted">Бронирований пока нет.</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>

			<div class="panel mb-4">
				<div class="panel-head">
					<h2>ПАРК КАРТОВ</h2>
					<span>{{ $karts->count() }} единиц</span>
				</div>

				<div class="row g-3">
					@foreach($karts as $kart)
						<div class="col-6 col-md-3">
							<div class="kart-tile">
								<div class="kart-number">{{ $kart->number }}</div>
								<b>{{ $kart->model }}</b>

								<form class="mt-2" method="POST" action="{{ route('staff.kart.update', $kart) }}">
									@csrf
									@method('PATCH')
									<select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
										<option value="available" @selected($kart->status === 'available')>Свободен</option>
										@if($kart->status === 'in_use')
											<option value="in_use" selected disabled>На трассе (назначен заезду)</option>
										@endif
										<option value="maintenance" @selected($kart->status === 'maintenance')>Обслуживание</option>
										<option value="inactive" @selected($kart->status === 'inactive')>Неактивен</option>
									</select>
								</form>
							</div>
						</div>
					@endforeach
				</div>
			</div>

			<div class="panel">
				<div class="panel-head">
					<div>
						<div class="eyebrow">RACE OPERATIONS</div>
						<h2>ЗАЕЗДЫ И РЕЗУЛЬТАТЫ КРУГОВ</h2>
					</div>
					<span>{{ $races->count() }} последних</span>
				</div>

				@forelse($races as $race)
					<div class="booking-row d-block mb-3">
						<div class="d-flex flex-wrap justify-content-between gap-2">
							<div>
								<strong>Заезд #{{ $race->id }}</strong> · {{ $race->booking->user->name ?? 'Клиент' }}
								<div class="small muted">
									{{ [
										'planned' => 'Запланирован',
										'running' => 'Идёт',
										'completed' => 'Завершён',
										'cancelled' => 'Отменён',
									][$race->status] ?? $race->status }}
								</div>
							</div>

							<div class="d-flex gap-2">
								@if($race->status === 'planned')
									<form method="POST" action="{{ route('staff.race.update', $race) }}">
										@csrf
										@method('PATCH')
										<input type="hidden" name="status" value="running">
										<button class="btn btn-sm btn-success">Начать заезд</button>
									</form>
								@endif

								@if($race->status === 'running')
									<form method="POST" action="{{ route('staff.race.update', $race) }}">
										@csrf
										@method('PATCH')
										<input type="hidden" name="status" value="completed">
										<button class="btn btn-sm btn-outline-light">Завершить</button>
									</form>
								@endif

								@if(in_array($race->status, ['planned', 'running']))
									<form method="POST" action="{{ route('staff.race.update', $race) }}">
										@csrf
										@method('PATCH')
										<input type="hidden" name="status" value="cancelled">
										<button class="btn btn-sm btn-outline-danger">Отменить</button>
									</form>
								@endif
							</div>
						</div>

						<div class="table-responsive mt-3">
							<table class="table table-dark table-sm align-middle">
								<thead>
									<tr>
										<th>Участник</th>
										<th>Карт</th>
										<th>Круг</th>
										<th>Время, сек.</th>
									</tr>
								</thead>
								<tbody>
									@foreach($race->participants as $participant)
										@forelse($participant->lapResults as $lap)
											<tr>
												<td>{{ $participant->full_name }}</td>
												<td>{{ $participant->kart->number }}</td>
												<td>{{ $lap->lap_number }}</td>
												<td>{{ number_format($lap->lap_time, 3, ',', ' ') }}</td>
											</tr>
										@empty
											<tr>
												<td>{{ $participant->full_name }}</td>
												<td>{{ $participant->kart->number }}</td>
												<td colspan="2" class="muted">Нет результатов</td>
											</tr>
										@endforelse
									@endforeach
								</tbody>
							</table>
						</div>

						@if($race->status === 'running')
							<form method="POST" action="{{ route('staff.race.lap.store', $race) }}" class="row g-2 align-items-end">
								@csrf

								<div class="col-md-5">
									<label class="form-label">Участник</label>
									<select class="form-select form-select-sm" name="race_participant_id" required>
										@foreach($race->participants as $participant)
											<option value="{{ $participant->id }}">
												{{ $participant->full_name }} · карт {{ $participant->kart->number }}
											</option>
										@endforeach
									</select>
								</div>

								<div class="col-md-2">
									<label class="form-label">Круг №</label>
									<input class="form-control form-control-sm" type="number" name="lap_number" min="1" max="1000" required>
								</div>

								<div class="col-md-2">
									<label class="form-label">Время, сек.</label>
									<input class="form-control form-control-sm" type="number" name="lap_time" min="1" max="3600" step="0.001" required>
								</div>

								<div class="col-md-3">
									<button class="btn btn-sm btn-accent w-100">Сохранить результат</button>
								</div>
							</form>
						@endif
					</div>
				@empty
					<p class="muted p-3">Заездов пока нет. Создайте заезд из подтверждённого бронирования.</p>
				@endforelse
			</div>
		</div>
	</section>
@endsection
