<?php
namespace App\Http\Controllers;
use App\Models\{Role,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function showLogin(){return view('auth.login');}
 public function login(Request $r){$data=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($data,$r->boolean('remember')))return back()->withErrors(['email'=>'Неверный адрес почты или пароль.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended(route('account'));}
 public function showRegister(){return view('auth.register');}
 public function register(Request $r){$data=$r->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','phone'=>'nullable|string|max:20','password'=>'required|string|min:8|confirmed']);$role=Role::where('slug','client')->firstOrFail();$user=User::create(['role_id'=>$role->id,'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password'=>Hash::make($data['password'])]);Auth::login($user);$r->session()->regenerate();return redirect()->route('account')->with('success','Аккаунт успешно создан.');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('home');}
}
