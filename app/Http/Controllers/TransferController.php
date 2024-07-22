<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\HttpResponses;
use App\Models\User;
use App\Models\Transfer;
use Carbon\Carbon;

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
        
        $id_sender = User::where('cpf_cnpj', $cpf_sender)->first()->id;
        $id_receiver = User::where('cpf_cnpj', $cpf_receiver)->first()->id;

        $create_transfer = Transfer::create([
            'user_id_sender' => $id_sender,
            'user_id_receiver' => $id_receiver,
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
