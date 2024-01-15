<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebhookCallRequest;
use App\Http\Requests\UpdateWebhookCallRequest;
use App\Models\WebhookCall;

class WebhookCallController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreWebhookCallRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWebhookCallRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WebhookCall  $webhookCall
     * @return \Illuminate\Http\Response
     */
    public function show(WebhookCall $webhookCall)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WebhookCall  $webhookCall
     * @return \Illuminate\Http\Response
     */
    public function edit(WebhookCall $webhookCall)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWebhookCallRequest  $request
     * @param  \App\Models\WebhookCall  $webhookCall
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWebhookCallRequest $request, WebhookCall $webhookCall)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WebhookCall  $webhookCall
     * @return \Illuminate\Http\Response
     */
    public function destroy(WebhookCall $webhookCall)
    {
        //
    }
}
