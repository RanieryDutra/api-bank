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

        $client = new Client();
        $url = 'https://util.devi.tools/api/v2/authorize';
        try {
            // Fazer a requisição GET com o cabeçalho de autenticação
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            // Obter o corpo da resposta
            $body = $response->getBody();
            $content = $body->getContents();

            // Decodificar o JSON
            $data = json_decode($content, true);

            // Verificar o que está vindo na resposta
            return response()->json($data);

        } catch (RequestException $e) {
            // Capturar e tratar qualquer erro na requisição
            if ($e->hasResponse()) {
                // Obter o código de status da resposta
                $statusCode = $e->getResponse()->getStatusCode();

                // Retornar uma resposta amigável
                return response()->json([
                    'error' => 'Erro ao acessar a API externa.',
                    'status' => $statusCode,
                ], $statusCode);
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
            'transfer_date' => Carbon::now()
        ]);

        if(!$create_transfer)
        {
            $erro = ['erros' => 'Erro na transferência'];
            return $this->error('Error Transfer', 422, $erro);
        }

        return $this->response('Success', 200);


    }
}