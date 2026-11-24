@extends('layouts.app')

@section('title', 'Вход')

@section('content')
	<section class="auth-wrap">
		<div class="auth-card">
			<div class="eyebrow">WELCOME BACK</div>
			<h1>СНОВА <span>В ИГРЕ.</span></h1>

			<form method="POST" action="{{ route('login.store') }}">
				@csrf

				<div class="mb-3">
					<label class="form-label">Электронная почта</label>
					<input
						class="form-control"
						type="email"
						name="email"
						value="{{ old('email') }}"
						required
					>
				</div>

				<div class="mb-3">
					<label class="form-label">Пароль</label>
					<input
						class="form-control"
						type="password"
						name="password"
						required
					>
				</div>

				<div class="form-check mb-4">
					<input class="form-check-input" type="checkbox" name="remember" id="remember">
					<label for="remember" class="form-check-label">Запомнить меня</label>
				</div>

				<button class="btn btn-accent btn-lg w-100">Войти</button>
			</form>

			<p class="auth-bottom">
				Нет аккаунта?
				<a href="{{ route('register') }}">Зарегистрироваться</a>
			</p>
		</div>
	</section>
@endsection
