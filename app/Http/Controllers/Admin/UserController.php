<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\UserDetail;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() 
    {
        $users = User::all();

        return view("admin.users.index", compact("users"));
    }

    public function edit($id) 
    {
        $user = User::findOrFail($id);

        return view("admin.users.edit", compact("user"));
    }

    public function update(Request $request, $id) 
    {
        $data = $request->all();
        $user = User::findOrFail($id);

        // $user->update($data);


        if(!$user->details) {
            $user->details = new UserDetail();
            $user->details->user_id = $user->id;
        }

        // assegnazione manuale dei dati 

        // $user->details->address = $data["address"];
        // $user->details->city = $data["city"];
        // $user->details->province = $data["province"];
        // $user->details->phone = $data["phone"];

        $user->details->fill($data);
        $user->details->save();

        return view("admin.users.edit", compact("user"));
    }


}
