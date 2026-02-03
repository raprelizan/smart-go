<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    public function index()
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return view('installer');
    }

    public function install(Request $request)
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'ACTIVE',
        ]);

        $user->assignRole('super-admin');

        file_put_contents(storage_path('installed.lock'), now()->toDateTimeString());

        return redirect('/admin');
    }
}
