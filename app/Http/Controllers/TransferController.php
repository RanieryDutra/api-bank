<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\HttpResponses;
use App\Models\User;
use App\Models\Transfer;
use Carbon\Carbon;
use GuzzleHttp\Client;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    use HttpResponses;

    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(),
        [
            'cpf_sender' => 'required|string|max:11|min:11',
            'cpf_receiver' => 'required|string|max:11|min:11',
            'value' => 'required|numeric'
        ]);

        if($validator->fails())
        {
            return $this->error('Data Invalid', 422, $validator->errors());
        }

        $cpf_sender = $request->all()['cpf_sender'];
        $cpf_receiver = $request->all()['cpf_receiver'];
        
        $sender = User::where('cpf_cnpj', $cpf_sender)->first();
        $receiver = User::where('cpf_cnpj', $cpf_receiver)->first();

        $balanceSender = $sender->balance - $validator->validate()['value'];
        $balanceReceiver = $receiver->balance + $validator->validate()['value'];

        dd($balanceSender, $balanceReceiver);

        $client = new Client();
        $url = 'https://util.devi.tools/api/v2/authorize';

        if($balanceSender < 0)
        {
            $error = ['error' => 'Balance sender is negative'];
            return $this->error('Balance insufficient', 422, $error);
        }

        try
        {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

        } catch (RequestException $e) 
        {
            if ($e->hasResponse())
            {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorCode = $e->getResponse()->getReasonPhrase();
                $erroAuth = ['error' => 'Falha na autorização'];

                return $this->error($errorCode, $statusCode, $erroAuth);
            }
        };

        if($sender->type == 'l')
        {
            $error_sender = ['erros' => 'Lojista não permitido realizar transferência.'];
            return $this->error('Data Invalid', 422, $error_sender);
        }
        
        if($sender->balance <= $validator->validate()['value'])
        {
            $error_balance = ['erros' => 'Saldo insuficiente.'];
            return $this->error('Data Invalid', 422, $error_balance);
        }

        

        $create_transfer = Transfer::create([
            'user_id_sender' => $sender->id,
            'user_id_receiver' => $receiver->id,
            'value' => $validator->validate()['value'],
            'transfer_date' => Carbon::now('America/Sao_Paulo')
        ]);

        //$balanceUser = User::where()

        if(!$create_transfer)
        {
            $erro = ['erros' => 'Erro na transferência'];
            return $this->error('Error Transfer', 422, $erro);
        }

        return $this->response('Success', 200);


    }
}