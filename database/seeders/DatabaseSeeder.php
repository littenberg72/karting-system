<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Role,User,Tariff,Kart};
class DatabaseSeeder extends Seeder {
 public function run(): void {
  foreach([['Гость','guest'],['Клиент','client'],['Сотрудник','staff'],['Администратор','admin']] as [$name,$slug]) Role::firstOrCreate(['slug'=>$slug],['name'=>$name]);
  $admin=Role::where('slug','admin')->first();
  User::firstOrCreate(['email'=>'admin@apex-karting.local'],['role_id'=>$admin->id,'name'=>'Администратор','phone'=>null,'password'=>Hash::make('ChangeMe123!')]);
  foreach([['Пробный заезд','Короткий заезд для знакомства с трассой',1200,10],['Стандарт','Оптимальный формат для регулярного заезда',2200,15],['Гран-при','Больше времени на трассе и борьбу за лучший круг',3500,25]] as [$name,$description,$price,$duration]) Tariff::firstOrCreate(['name'=>$name],['description'=>$description,'price'=>$price,'duration'=>$duration,'is_active'=>true]);
  for($i=1;$i<=8;$i++) Kart::firstOrCreate(['number'=>str_pad((string)$i,2,'0',STR_PAD_LEFT)],['model'=>'Sport Kart','status'=>'available','description'=>'Демонстрационная запись — замените данные на реальные.']);
 }
}
