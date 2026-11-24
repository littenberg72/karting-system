@extends('layouts.app')

@section('title', 'Тарифы')

@section('content')
	<section class="page-hero">
		<div class="container">
			<div class="eyebrow">PRICING / 02</div>
			<h1>ВЫБЕРИ СВОЙ <span>РИТМ.</span></h1>
			<p>Подходящий формат для первого знакомства с трассой и полноценной гонки.</p>
		</div>
	</section>

	<section class="section-pad">
		<div class="container">
			<div class="row g-4">
				@forelse($tariffs as $i => $tariff)
					<div class="col-md-4">
						<article class="price-card h-100">
							<div class="placeholder placeholder-tariff">
								<span>ФОТО · 800 × 480</span>
							</div>

							<div class="eyebrow mt-4">SESSION 0{{ $i + 1 }}</div>
							<h3>{{ $tariff->name }}</h3>
							<p>{{ $tariff->description }}</p>
							<div class="price">
								{{ number_format($tariff->price, 0, ',', ' ') }} ₽
								<small>/ {{ $tariff->duration }} мин.</small>
							</div>

							<a class="btn btn-accent w-100 mt-3" href="{{ auth()->check() ? route('booking.create') : route('register') }}">
								Забронировать
							</a>
						</article>
					</div>
				@empty
					<p>Активных тарифов пока нет.</p>
				@endforelse
			</div>
		</div>
	</section>
@endsection
