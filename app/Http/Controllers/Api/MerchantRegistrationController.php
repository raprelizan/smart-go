<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MerchantRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:merchants,email',
            'phone' => 'required|string|max:32',
            'password' => 'required|confirmed|min:8',
            'whatsapp_receive_number' => 'required|string|max:32',
            'accept_terms' => 'accepted',
            'accept_privacy' => 'accepted',
        ]);

        $merchant = Merchant::create([
            'full_name' => $data['full_name'],
            'business_name' => $data['business_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'whatsapp_receive_number' => $data['whatsapp_receive_number'],
            'status' => 'PENDING',
            'slug' => str($data['business_name'])->slug('-'),
        ]);

        User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'merchant_id' => $merchant->id,
            'status' => 'PENDING',
        ])->assignRole('merchant');

        return response()->json(['message' => 'Registration submitted']);
    }
}
