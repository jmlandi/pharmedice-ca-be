<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : O email de destino para o teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o envio de email para verificar se a configuração está funcionando';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Enviando email de teste para: {$email}");
        
        try {
            Mail::to($email)->send(new TestMail());
            $this->info('✅ Email enviado com sucesso!');
        } catch (\Exception $e) {
            $this->error('❌ Erro ao enviar email: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
