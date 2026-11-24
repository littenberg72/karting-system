@extends('layouts.app')

@section('title', 'Главная')

@section('content')
	<section class="hero">
		<div class="hero-image placeholder placeholder-hero">
			<span>ФОТО ТРАССЫ · 1920 × 900</span>
		</div>

		<div class="container hero-content">
			<div class="eyebrow">INDOOR KARTING · RACE YOUR LIMITS</div>
			<h1>ТВОЯ СКОРОСТЬ.<br><span>ТВОИ ПРАВИЛА.</span></h1>
			<p>Почувствуй трассу, найди идеальную траекторию и поборись за лучшее время круга.</p>

			<div class="d-flex gap-3 flex-wrap">
				<a class="btn btn-accent btn-lg" href="{{ auth()->check() ? route('booking.create') : route('register') }}">
					Забронировать заезд <span>↗</span>
				</a>
				<a class="btn btn-ghost btn-lg" href="{{ route('tariffs') }}">Выбрать тариф</a>
			</div>
		</div>

		<div class="hero-index">01 / 04 <span>—</span> APEX CIRCUIT</div>
	</section>

	<section class="section-pad" id="track">
		<div class="container">
			<div class="section-head">
				<div>
					<div class="eyebrow">THE EXPERIENCE</div>
					<h2>БОЛЬШЕ, ЧЕМ <span>КАРТИНГ.</span></h2>
				</div>
				<p>Трасса, где важны не только скорость, но и точность. Приезжай один или собирай свою команду.</p>
			</div>

			<div class="feature-grid">
				<div class="feature-card feature-wide">
					<div class="placeholder placeholder-card">
						<span>ФОТО ТРАССЫ · 1200 × 700</span>
					</div>
					<div class="feature-caption">
						<b>01</b>
						<div>
							<h3>ТРАССА</h3>
							<p>Технические повороты и динамичные прямые.</p>
						</div>
					</div>
				</div>

				<div class="feature-card">
					<div class="placeholder placeholder-card">
						<span>ФОТО КАРТА · 800 × 700</span>
					</div>
					<div class="feature-caption">
						<b>02</b>
						<div>
							<h3>ТЕХНИКА</h3>
							<p>Подготовленные карты и экипировка.</p>
						</div>
					</div>
				</div>

				<div class="feature-card">
					<div class="placeholder placeholder-card">
						<span>ФОТО ГОНОК · 800 × 700</span>
					</div>
					<div class="feature-caption">
						<b>03</b>
						<div>
							<h3>СОРЕВНОВАНИЕ</h3>
							<p>Сравнивай результаты и улучшай время.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="section-pad section-dark">
		<div class="container">
			<div class="section-head">
				<div>
					<div class="eyebrow">CHOOSE YOUR RACE</div>
					<h2>ТВОЙ <span>ЗАЕЗД.</span></h2>
				</div>
				<a class="text-link" href="{{ route('tariffs') }}">Все тарифы ↗</a>
			</div>

			<div class="row g-4">
				@foreach($tariffs as $i => $tariff)
					<div class="col-md-4">
						<article class="price-card">
							<div class="eyebrow">SESSION 0{{ $i + 1 }}</div>
							<h3>{{ $tariff->name }}</h3>
							<p>{{ $tariff->description }}</p>
							<div class="price">
								{{ number_format($tariff->price, 0, ',', ' ') }} ₽
								<small>/ {{ $tariff->duration }} мин.</small>
							</div>
							<a class="btn btn-outline-light w-100" href="{{ auth()->check() ? route('booking.create') : route('register') }}">
								Выбрать тариф
							</a>
						</article>
					</div>
				@endforeach
			</div>
		</div>
	</section>

	<section class="cta-band">
		<div class="container d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
			<div>
				<div class="eyebrow">READY TO RACE?</div>
				<h2>УВИДИМСЯ <span>НА ТРАССЕ.</span></h2>
			</div>
			<a class="btn btn-accent btn-lg" href="{{ auth()->check() ? route('booking.create') : route('register') }}">
				Забронировать время ↗
			</a>
		</div>
	</section>
@endsection
