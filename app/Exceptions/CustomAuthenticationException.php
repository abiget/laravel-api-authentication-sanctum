<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;

class CustomAuthenticationException extends AuthenticationException
{
    public function render(){
        return response()->json(['error' => $this->getMessage()], 401);
    }
}
