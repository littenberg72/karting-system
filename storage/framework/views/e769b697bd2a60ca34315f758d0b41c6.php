<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $__env->yieldContent('title','APEX Karting'); ?> — картодром</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo e(asset('css/app.css')); ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark site-nav"><div class="container"><a class="navbar-brand brand" href="<?php echo e(route('home')); ?>">APEX<span>.</span></a><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="nav"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3"><li><a class="nav-link" href="<?php echo e(route('tariffs')); ?>">Тарифы</a></li><li><a class="nav-link" href="<?php echo e(route('home')); ?>#track">Трасса</a></li><?php if(auth()->guard()->check()): ?><li><a class="nav-link" href="<?php echo e(route('account')); ?>">Личный кабинет</a></li><li><form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-light btn-sm">Выйти</button></form></li><?php else: ?><li><a class="nav-link" href="<?php echo e(route('login')); ?>">Войти</a></li><li><a class="btn btn-accent btn-sm px-3" href="<?php echo e(route('register')); ?>">Записаться</a></li><?php endif; ?></ul></div></div></nav>
<?php if(session('success')): ?><div class="container mt-3"><div class="alert alert-success"><?php echo e(session('success')); ?></div></div><?php endif; ?>
<?php if($errors->any()): ?><div class="container mt-3"><div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div></div><?php endif; ?>
<main><?php echo $__env->yieldContent('content'); ?></main>
<footer class="footer"><div class="container d-flex flex-column flex-md-row justify-content-between gap-3"><div><div class="brand">APEX<span>.</span></div><small>Картинг, скорость и характер.</small></div><small>© <?php echo e(date('Y')); ?> APEX Karting. Демонстрационный проект.</small></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="<?php echo e(asset('js/app.js')); ?>"></script>
</body></html>
<?php /**PATH /var/home/regari/Загрузки/karting-system/resources/views/layouts/app.blade.php ENDPATH**/ ?>