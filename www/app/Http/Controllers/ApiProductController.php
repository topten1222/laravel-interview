<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiProductController extends Controller
{
    public function create(Request $request)
    {
        if (!$request->user()->tokenCan('Admin')) {
            return response()->json([
                'message' => 'permission denied'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:product|max:255',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'category' => 'required|string|max:255',
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'error' => $errors
            ], 400);
        }

        Product::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'category' => $request->category,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'success',
        ]);
    }
}
