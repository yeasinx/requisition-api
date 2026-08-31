<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemSettingsRequest;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Models\SystemSettings;

class SystemSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSystemSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SystemSettings $systemSettings)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SystemSettings $systemSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSystemSettingsRequest $request, SystemSettings $systemSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SystemSettings $systemSettings)
    {
        //
    }
}
