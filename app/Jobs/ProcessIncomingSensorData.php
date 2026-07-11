<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\SensorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingSensorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Device $device,
        public array $payload
    ) {}

    public function handle(SensorService $sensorService): void
    {
        $sensorService->processIncomingData($this->device, $this->payload);
    }
}