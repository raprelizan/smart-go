<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;

class MerchantApprovalController extends Controller
{
    public function index()
    {
        return Merchant::query()->latest()->get();
    }

    public function approve(Request $request, Merchant $merchant)
    {
        $merchant->update(['status' => 'ACTIVE', 'notes' => $request->input('notes')]);
        User::where('merchant_id', $merchant->id)->update(['status' => 'ACTIVE']);

        return response()->json(['message' => 'Merchant approved']);
    }

    public function reject(Request $request, Merchant $merchant)
    {
        $merchant->update(['status' => 'REJECTED', 'notes' => $request->input('notes')]);
        User::where('merchant_id', $merchant->id)->update(['status' => 'REJECTED']);

        return response()->json(['message' => 'Merchant rejected']);
    }
}
