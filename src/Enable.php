<?php

namespace VitoDeploy\LaravelReverbPlugin;

use App\Actions\Worker\CreateWorker;
use App\Actions\Worker\ManageWorker;
use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\Models\Worker;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Enable extends Action
{
    public function name(): string
    {
        return 'Enable';
    }

    public function active(): bool
    {
        return ! data_get($this->site->type_data, 'reverb', false);
    }

    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('port_alert')
                ->alert()
                ->options(['type' => 'warning'])
                ->description('Make sure this port is not used by any other service on the server.'),
            DynamicField::make('port')
                ->text()
                ->description('This is the port reverb will run on the server. This port is not exposed to internet!')
                ->default(8080),
            DynamicField::make('command')
                ->text()
                ->label('Command')
                ->default('php artisan reverb:start')
                ->description('The command to start Laravel Reverb'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        Validator::make($request->all(), [
            'port' => 'required|integer|min:1|max:65535',
            'command' => 'required|string',
        ])->validate();

        /** @var ?Worker $worker */
        $worker = $this->site->workers()->where('name', 'laravel-reverb')->first();
        if ($worker) {
            app(ManageWorker::class)->restart($worker);
        } else {
            app(CreateWorker::class)->create(
                $this->site->server,
                [
                    'name' => 'laravel-reverb',
                    'command' => $request->input('command'),
                    'user' => $this->site->user ?? $this->site->server->getSshUser(),
                    'auto_start' => true,
                    'auto_restart' => true,
                    'numprocs' => 1,
                ],
                $this->site,
            );
        }

        $typeData = $this->site->type_data ?? [];
        data_set($typeData, 'reverb', true);
        data_set($typeData, 'reverb_port', $request->input('port'));
        $this->site->type_data = $typeData;
        $this->site->save();

        $this->updateVHost();

        $request->session()->flash('success', 'Laravel Reverb has been enabled for this site.');
    }

    private function updateVHost(): void
    {
        $this->site->refresh();
        $webserver = $this->site->webserver();

        $this->site->webserver()->updateVHost(
            $this->site,
            regenerate: ['php'],
            append: [
                'php' => view('vitodeploy-reverb::'.$webserver->id(), ['port' => $this->site->type_data['reverb_port']]),
            ]
        );
    }
}
