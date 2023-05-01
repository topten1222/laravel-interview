<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $res = [
            'message' => 'success',
            'items' => $products
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);
        return response()->json($res);
    }

    public function create(Request $request)
    {
        if (!$request->user()->tokenCan('Admin')) {
            $res = [
                'message' => 'permission denied'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:product|max:255',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'category' => 'required|string|max:255',
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            $res = [
                'error' => $errors
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 400);
        }

        Product::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'category' => $request->category,
            'created_by' => $request->user()->id,
        ]);
        $res = [
            'message' => 'success',
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);

        return response($res)->json();
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->tokenCan('Admin') && !$request->user()->tokenCan('Editor')) {
            $res = [
                'message' => 'permission denied'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'status_code' => 400,
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 403);
        }
        $product = Product::where(['id' => $id])->first();
        if (!$product) {
            $res = [
                'message' => 'not found product'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'status_code' => 400,
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:product|max:255',
            'category' => 'string|max:255',
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            $res = [
                'error' => $errors
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'status_code' => 400,
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 400);
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
                $res = [
                    'message' => 'Can not update product',
                    'status' => 'failed'
                ];
                Log::channel('request')->info('request api url '.$request->url(), [
                    'status_code' => 400,
                    'url' => $request->url(),
                    'request' => $request->all(),
                    'date' => date('d-m-Y H:i:s'),
                    'ip' => $request->ip(),
                    'response' => $res
                ]);
                return response($res, 400);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack(); // Tell Laravel, "It's not you, it's me. Please don't persist to DB"
            $res = [
                'message' => $e->getMessage(),
                'status' => 'failed'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'status_code' => 400,
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response($res, 400);
        }
        $res = [
            'message' => 'success',
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'status_code' => 400,
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);
        return response($res)->json();
    }

    public function lists(Request $request)
    {
        $currentPage = $request->get('page',1);

        $cachedProducts = Redis::get('product_page_' . $currentPage);
        if(isset($cachedProducts)) {
            $products = json_decode($cachedProducts, FALSE);
            $res = [
                'message' => 'Fetched from redis',
                'data' => $products,
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res);
        }
        $products = Product::query()->select([
            'id',
            'name',
            'amount',
            'category'
        ])->orderBy('updated_at', 'DESC')->paginate(100);
        Redis::set('product_page_' . $currentPage, json_encode($products), 'EX', 20);
        $res = [
            'message' => 'success',
            'items' => $products
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);
        return response()->json($res);
    }
}
