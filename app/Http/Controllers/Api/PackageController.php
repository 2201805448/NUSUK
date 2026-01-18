<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;

class PackageController extends Controller
{
    // List all packages
    // 1. تحديث دالة الـ index لإرسال بيانات الفندق
    public function index(Request $request)
    {
        $query = Package::with('accommodation'); // إضافة الفندق للنتائج

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->get());
    }

    // Get specific package details
    public function show($id)
    {
        $package = Package::with('accommodation')->findOrFail($id);
        return response()->json($package);
    }

    // Store a new package
    public function store(Request $request)
    {
        $request->validate([
            'package_name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'accommodation_id' => 'required|exists:accommodations,accommodation_id',
            'room_type' => 'required|string',
            'description' => 'nullable|string',
            'services' => 'nullable|string',
            'mod_policy' => 'nullable|string',
            'cancel_policy' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $package = Package::create([
            'package_name' => $request->package_name,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'accommodation_id' => $request->accommodation_id,
            'room_type' => $request->room_type,
            'description' => $request->description,
            'services' => $request->services,
            'mod_policy' => $request->mod_policy,
            'cancel_policy' => $request->cancel_policy,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'message' => 'Package created successfully',
            'package' => $package
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        // 1. التحقق من البيانات (Validation)
        $request->validate([
            'package_name' => 'sometimes|string|max:150',
            'price' => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'accommodation_id' => 'sometimes|exists:accommodations,accommodation_id',
            'room_type' => 'sometimes|string',
            'description' => 'nullable|string',
            'services' => 'nullable|array', // تأكدنا أنها مصفوفة
            'mod_policy' => 'nullable|string',
            'cancel_policy' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        // 2. تجهيز البيانات للتحديث (Data Mapping)
        $data = $request->only([
            'package_name',
            'price',
            'duration_days',
            'accommodation_id',
            'room_type',
            'description',
            'mod_policy',
            'cancel_policy'
        ]);

        // معالجة حقل الخدمات (Mapping services from features)
        if ($request->has('services')) {
            $data['services'] = $request->services;
        }

        // معالجة حالة النشاط (Mapping is_active from status/is_active)
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        // 3. تنفيذ التحديث في قاعدة البيانات
        $package->update($data);

        return response()->json([
            'message' => 'تم تحديث الباقة بنجاح',
            'package' => $package->load('accommodation') // تحميل بيانات الفندق الجديدة في الرد
        ]);
    }

    // Delete a package
    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully'
        ]);
    }

    /**
     * Get reviews for trips associated with this package.
     * Returns anonymous reviews.
     */
    public function getTripReviews($id)
    {
        $package = Package::findOrFail($id);

        // Get all trip IDs for this package
        $tripIds = \App\Models\Trip::where('package_id', $id)->pluck('trip_id');

        // Find evaluations where type='TRIP' and target_id IN $tripIds and internal_only=0
        $reviews = \App\Models\Evaluation::where('type', 'TRIP')
            ->whereIn('target_id', $tripIds)
            ->where('internal_only', 0)
            ->select('score', 'concern_text', 'created_at') // Anonymous
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'package_name' => $package->package_name,
            'reviews' => $reviews
        ]);
    }
}
