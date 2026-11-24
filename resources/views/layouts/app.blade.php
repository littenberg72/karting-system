<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title', 'APEX Karting') — картодром</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark site-nav">
		<div class="container">
			<a class="navbar-brand brand" href="{{ route('home') }}">APEX<span>.</span></a>
			<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="nav">
				<ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
					<li>
						<a class="nav-link" href="{{ route('tariffs') }}">Тарифы</a>
					</li>
					<li>
						<a class="nav-link" href="{{ route('home') }}#track">Трасса</a>
					</li>

					@auth
						<li>
							<a class="nav-link" href="{{ route('account') }}">Личный кабинет</a>
						</li>
						<li>
							<form method="POST" action="{{ route('logout') }}">
								@csrf
								<button class="btn btn-outline-light btn-sm">Выйти</button>
							</form>
						</li>
					@else
						<li>
							<a class="nav-link" href="{{ route('login') }}">Войти</a>
						</li>
						<li>
							<a class="btn btn-accent btn-sm px-3" href="{{ route('register') }}">Записаться</a>
						</li>
					@endauth
				</ul>
			</div>
		</div>
	</nav>

	@if(session('success'))
		<div class="container mt-3">
			<div class="alert alert-success">{{ session('success') }}</div>
		</div>
	@endif

	@if($errors->any())
		<div class="container mt-3">
			<div class="alert alert-danger">
				<ul class="mb-0">
					@foreach($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		</div>
	@endif

	<main>
		@yield('content')
	</main>

	<footer class="footer">
		<div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
			<div>
				<div class="brand">APEX<span>.</span></div>
				<small>Картинг, скорость и характер.</small>
			</div>
			<small>© {{ date('Y') }} APEX Karting. Демонстрационный проект.</small>
		</div>
	</footer>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
