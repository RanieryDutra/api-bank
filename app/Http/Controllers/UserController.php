<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use HttpResponses;

    public function CreateUser(Request $request) {

        $validator = Validator::make($request->all(),
        [
            'full_name' => 'required|string|max:50|min:10',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'cpf_cnpj' => 'required|string|min:11|max:11|unique:users',
            'type' => 'required|string|min:1|max:1'
        ]);

        if($validator->fails()) 
        {
            //return response()->json(['message' => 'Error creating user'], 400);
            return $this->error('Data Invalid', 422, $validator->errors());
        }
            
        $created_user = User::create($validator->validate());

        if($created_user) 
        {
        return $this->response('Success', 200);
            //return response()->json(['message' => 'Sucsses Create User'], 200);
        }
    }


    public function Deposit(Request $request, string $cpf)
    {
        $validator = Validator::make($request->all(),
        [
            'balance' => 'required|numeric'
        ]);

        if($validator->fails())
        {
            return $this->error('Data Invalid', 422, $validator->errors());
        }

        $validated_data = $validator->validated();

        $balanceUser = User::where('cpf_cnpj', $cpf)->first()->balance + $validated_data['balance'];

        $updated = User::where('cpf_cnpj', $cpf)->update([
            'balance' => $balanceUser
        ]);

        if($updated)
        {
            return $this->response('Success', 200);
        }

        $error = ['errors' => 'cpf invalido'];

        return $this->error('Data Invalid', 422, $error);

    }
}
