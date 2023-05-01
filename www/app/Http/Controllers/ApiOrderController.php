<?php

namespace App\Http\Controllers;

use App\Order;
use App\OrderDetail;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiOrderController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'phone' => 'required|numeric|digits:10',
            'address' => 'required|string',
            'address_tax' => 'string',
            'items.*.product_id' => 'required|exists:product,id',
            'items.*.product_quantity' => 'required|numeric'
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            $res = [
                'error' => $errors
            ];
            Log::channel('request')->info('request api url ' . $request->url(), [
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
            $order = new Order();
            $order->code = $this->getNextOrderNumber();
            $order->email = $request->email;
            $order->phone = $request->phone;
            $order->address = $request->address;
            $order->address_tax = !empty($request->address_tax) ? $request->address_tax : null;
            if (!$order->save()) {
                DB::rollBack();
                $res = [
                    'message' => 'Can not save order',
                    'status' => 'failed'
                ];
                Log::channel('request')->info('request api url ' . $request->url(), [
                    'status_code' => 400,
                    'url' => $request->url(),
                    'request' => $request->all(),
                    'date' => date('d-m-Y H:i:s'),
                    'ip' => $request->ip(),
                    'response' => $res
                ]);
                return response($res, 400);
            }
            foreach ($request->items as $item) {
                $product = Product::where(['id' => $item['product_id']])->first();
                if (!$product) {
                    DB::rollBack();
                    $res = [
                        'message' => 'not found product'
                    ];
                    Log::channel('request')->info('request api url ' . $request->url(), [
                        'status_code' => 400,
                        'url' => $request->url(),
                        'request' => $request->all(),
                        'date' => date('d-m-Y H:i:s'),
                        'ip' => $request->ip(),
                        'response' => $res
                    ]);
                    return response()->json($res, 404);
                }
                $orderDetail = new OrderDetail();
                $orderDetail->product_id = $item['product_id'];
                $orderDetail->product_name = $product->name;
                $orderDetail->product_price = $product->amount;
                $orderDetail->product_category = $product->category;
                $orderDetail->product_quantity = $item['product_quantity'];
                $orderDetail->order_id = $order->id;
                if (!$orderDetail->save()) {
                    DB::rollBack();
                    $res = [
                        'message' => 'Can not save order detail',
                        'status' => 'failed'
                    ];
                    Log::channel('request')->info('request api url ' . $request->url(), [
                        'status_code' => 400,
                        'url' => $request->url(),
                        'request' => $request->all(),
                        'date' => date('d-m-Y H:i:s'),
                        'ip' => $request->ip(),
                        'response' => $res
                    ]);
                    return response($res, 400);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack(); // Tell Laravel, "It's not you, it's me. Please don't persist to DB"
            $res = [
                'message' => $e->getMessage(),
                'status' => 'failed'
            ];
            Log::channel('request')->info('request api url ' . $request->url(), [
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
        Log::channel('request')->info('request api url ' . $request->url(), [
            'status_code' => 400,
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);
        return response($res, 200);
    }

    public function getNextOrderNumber()
    {
        $lastOrder = Order::orderBy('created_at', 'desc')->first();
        if (!$lastOrder) {
            $number = 0;
        } else {
            $number = substr($lastOrder->order_id, 3);
        }
        return 'ORD_' . date('Y-m-d') . '_' . sprintf('%06d', intval($number) + 1);
    }
}
