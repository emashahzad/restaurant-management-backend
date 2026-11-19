<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class MenuController extends Controller
{
    public function index()
    {
        $menu = Menu::all();
        return response()->json([
            'success' => true,
            'message' => "Menus fetched successfully",
            'menus' => $menu
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0'
        ]);
        $menu = Menu::create($data);
        return response()->json([
            'success' => true,
            'message' => "Menu created successfully",
            'menus' => $menu
        ], 201);
    }
    public function show(Menu $menu)
    {
        return response()->json([
            'success' => true,
            'message' => 'Menu fetched successfully',
            'menus' => $menu
        ], 200);
    }
    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0'
        ]);
        $menu->update($data);
        return response()->json([
            'success' => true,
            'message' => "Menu Updated successfully",
            'menus' => $menu
        ], 200);
    }

    public function patch(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0'
        ]);
        $menu->update($data);
        return response()->json([
            'success' => true,
            'message' => "Menu Updated successfully",
            'menus' => $menu
        ], 200);
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return response()->json([
            'success' => true,
            'message' => "Menu deleted successfully",
            'menus' => $menu
        ], 200);

    }

    public function trash()
    {
        $menu = Menu::onlyTrashed()->get();

        return response()->json([
            'message' => 'Deleted menus fetched successfully',
            'data' => $menu
        ], 200);
    }
    public function restore($id)
    {
        $menu = Menu::withTrashed()->find($id);
        if (!$menu) {
            return response()->json([
                'message' => 'menu not found'
            ], 400);
        }
        if (!$menu->trashed()) {
            return response()->json([
                "message" => 'Menu is not deleted'
            ], 400);
        }
        $menu->restore();
        return response()->json([
            'message' => 'Menu restored successfully',
            'data'=> $menu
        ], 200);
    }

public function forceDelete($id)
{
    // Find only deleted menus
    $menu = Menu::onlyTrashed()->find($id);

    if (!$menu) {
        return response()->json([
            'message' => 'Deleted menu not found'
        ], 404);
    }

    try {
        DB::transaction(function () use ($menu) {
            $menu->forceDelete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Menu permanently deleted successfully'
        ], 200);

    } catch (QueryException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Menu cannot be permanently deleted because related records exist.'
        ], 409);
    }
}

}
