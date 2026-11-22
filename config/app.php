<?php
return [
'name'=>env('APP_NAME','APEX Karting'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost'),'asset_url'=>env('ASSET_URL'),'timezone'=>'Europe/Berlin','locale'=>'ru','fallback_locale'=>'en','faker_locale'=>'ru_RU','key'=>env('APP_KEY'),'cipher'=>'AES-256-CBC','maintenance'=>['driver'=>'file','store'=>'database'],
'providers'=>Illuminate\Support\ServiceProvider::defaultProviders()->merge([App\Providers\AppServiceProvider::class,App\Providers\RouteServiceProvider::class])->toArray(),
'aliases'=>Illuminate\Support\Facades\Facade::defaultAliases()->toArray(),
];
