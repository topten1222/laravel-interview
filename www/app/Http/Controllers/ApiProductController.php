<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ApiProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()->select([
            'name',
            'amount',
            'category'
        ])->orderBy('updated_at', 'DESC')->paginate(100);
        return response()->json([
            'message' => 'success',
            'items' => $products
        ]);
    }

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

    public function update(Request $request, string $id)
    {
        if (!$request->user()->tokenCan('Admin') && !$request->user()->tokenCan('Editor')) {
            return response()->json([
                'message' => 'permission denied'
            ], 403);
        }
        $product = Product::where(['id' => $id])->first();
        if (!$product) {
            return response()->json([
                'message' => 'not found product'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:product|max:255',
            'category' => 'string|max:255',
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'error' => $errors
            ], 400);
        }
        DB::beginTransaction();
        try {
            ProductLog::create([
                'name' => $product->name,
                'amount' => $product->amount,
                'category' => $product->category,
                'created_by' => $product->created_by,
                'product_id' => $product->id
            ]);
            $product->name = !empty($request->name) ? $request->name : $product->name;
            $product->category = !empty($request->category) ? $request->category : $product->category;
            if (!$product->save()) {
                DB::rollBack();
                return response([
                    'message' => 'Can not update product',
                    'status' => 'failed'
                ], 400);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack(); // Tell Laravel, "It's not you, it's me. Please don't persist to DB"
            return response([
                'message' => $e->getMessage(),
                'status' => 'failed'
            ], 400);
        }

        return response()->json([
            'message' => 'success',
        ]);
    }

    public function lists(Request $request)
    {
        $currentPage = $request->get('page',1);

        $cachedProducts = Redis::get('product_page_' . $currentPage);
        if(isset($cachedProducts)) {
            $products = json_decode($cachedProducts, FALSE);
            return response()->json([
                'message' => 'Fetched from redis',
                'data' => $products,
            ]);
        }
        $products = Product::query()->select([
            'id',
            'name',
            'amount',
            'category'
        ])->orderBy('updated_at', 'DESC')->paginate(100);
        Redis::set('product_page_' . $currentPage, json_encode($products), 'EX', 20);
        return response()->json([
            'message' => 'success',
            'items' => $products
        ]);
    }
}
