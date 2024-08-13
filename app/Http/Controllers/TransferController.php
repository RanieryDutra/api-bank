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

        //dd($updateBalanceReceiver);

        $client = new Client();
        $urlAuthorize = 'https://util.devi.tools/api/v2/authorize';
        $urlEmail = 'https://util.devi.tools/api/v1/notify';

        if($balanceSender < 0)
        {
            $error = ['error' => 'Balance sender is negative'];
            return $this->error('Balance insufficient', 422, $error);
        }

        try
        {
            $responseAuthorize = $client->request('GET', $urlAuthorize, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            $responseAuthorize;

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

        try
        {
            $responseEmail = $client->request('POST', $urlEmail, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            $responseEmail;

        } catch (RequestException $e) 
        {
            if ($e->hasResponse())
            {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorCode = $e->getResponse()->getReasonPhrase();
                $erroAuth = ['error' => 'Falha no envio do Email'];

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

        $update_balance_sender = User::where('cpf_cnpj', $cpf_sender)->update(['balance' => $balanceSender]);
        $update_balance_receiver = User::where('cpf_cnpj', $cpf_receiver)->update(['balance' => $balanceReceiver]);

        if(!$update_balance_sender || !$update_balance_receiver)
        {
            $erro = ['erros' => 'Erro atualização do saldo no usuário.'];
            return $this->error('Error Transfer', 422, $erro);
        }

        $create_transfer = Transfer::create([
            'user_id_sender' => $sender->id,
            'user_id_receiver' => $receiver->id,
            'value' => $validator->validate()['value'],
            'transfer_date' => Carbon::now('America/Sao_Paulo')
        ]);

        if(!$create_transfer)
        {
            $erro = ['erros' => 'Erro na criação da transfêrencia.'];
            return $this->error('Error Transfer', 422, $erro);
        }

        $dataSuccess = [ 'value' => $validator->validate()['value'], 'cpf_sender' => $cpf_sender, 'cpf_receiver' => $cpf_receiver];

        return $this->response('Transfer completed successfully', 200, $dataSuccess);


    }
}