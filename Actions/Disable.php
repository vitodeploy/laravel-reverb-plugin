<?php

namespace App\Vito\Plugins\Vitodeploy\LaravelReverbPlugin\Actions;

use App\Actions\Worker\DeleteWorker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;

class Disable extends Action
{
    public function name(): string
    {
        return 'Disable';
    }

    public function active(): bool
    {
        return data_get($this->site->type_data, 'reverb', false);
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('confirm')
                ->alert()
                ->description('Are you sure you want to disable Laravel Reverb for this site?')
                ->options(['type' => 'warning']),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $typeData = $this->site->type_data ?? [];

        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'laravel-reverb')->first();
        if ($worker) {
            app(DeleteWorker::class)->delete($worker);
        }

        unset($typeData['reverb']);
        unset($typeData['reverb_port']);
        $this->site->type_data = $typeData;
        $this->site->save();

        $this->site->webserver()->updateVHost(
            $this->site,
            replace: [
                'laravel-reverb' => '',
            ],
        );

        $request->session()->flash('success', 'Laravel Reverb has been disabled for this site.');
    }
}
